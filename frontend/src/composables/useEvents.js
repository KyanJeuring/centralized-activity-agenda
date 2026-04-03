import { ref } from "vue";

const API_BASE =
  import.meta.env.VITE_API_BASE || "http://localhost:8000/api/v1";
const AUTO_REFRESH_MS = 30000;

const _events = ref([]);
const _isLoading = ref(false);
const _error = ref(null);

let _inFlightRequest = null;
let _refreshIntervalId = null;
let _releaseAutoRefresh = null;
let _autoRefreshConsumers = 0;

export function useEvents() {
  async function fetchEvents(options = {}) {
    const { force = false, background = false } = options;
    const shouldShowLoading = !background || _events.value.length === 0;

    if (!force && _events.value.length > 0) {
      return _events.value;
    }

    if (_inFlightRequest) {
      return _inFlightRequest;
    }

    if (shouldShowLoading) {
      _isLoading.value = true;
    }

    if (!background) {
      _error.value = null;
    }

    _inFlightRequest = (async () => {
      const response = await fetch(`${API_BASE}/events`);

      if (!response.ok) {
        throw new Error(`STATUS: ${response.status}`);
      }

      const data = await response.json();
      _events.value = normaliseEvents(data.data);
      return _events.value;
    })();

    try {
      return await _inFlightRequest;
    } catch (err) {
      _error.value = err.message;
      console.error("[useEvents] fetch failed: ", err);
      throw err;
    } finally {
      _inFlightRequest = null;
      if (shouldShowLoading) {
        _isLoading.value = false;
      }
    }
  }

  function startAutoRefresh(options = {}) {
    const { intervalMs = AUTO_REFRESH_MS } = options;
    _autoRefreshConsumers += 1;

    if (!_releaseAutoRefresh) {
      const refreshInBackground = () => {
        fetchEvents({ force: true, background: true }).catch(() => {
          // Keep polling even when one request fails.
        });
      };

      const handleVisible = () => {
        if (document.visibilityState === "visible") {
          refreshInBackground();
        }
      };

      const handleFocus = () => {
        refreshInBackground();
      };

      _refreshIntervalId = window.setInterval(refreshInBackground, intervalMs);
      document.addEventListener("visibilitychange", handleVisible);
      window.addEventListener("focus", handleFocus);

      _releaseAutoRefresh = () => {
        if (_refreshIntervalId !== null) {
          window.clearInterval(_refreshIntervalId);
          _refreshIntervalId = null;
        }
        document.removeEventListener("visibilitychange", handleVisible);
        window.removeEventListener("focus", handleFocus);
        _releaseAutoRefresh = null;
      };

      refreshInBackground();
    }

    return () => {
      _autoRefreshConsumers = Math.max(0, _autoRefreshConsumers - 1);
      if (_autoRefreshConsumers === 0) {
        _releaseAutoRefresh?.();
      }
    };
  }

  return {
    events: _events,
    isLoading: _isLoading,
    error: _error,
    fetchEvents,
    startAutoRefresh,
  };
}

function normaliseEvents(rawData) {
  return rawData.map((e) => ({
    id: e.id,
    title: e.title ?? e.name ?? "",
    organizer: e.organizer,
    start_date: e.start_date,
    description: e.description,
    location: e.location,
    url: e.url,
    img: e.img ?? "",
  }));
}
