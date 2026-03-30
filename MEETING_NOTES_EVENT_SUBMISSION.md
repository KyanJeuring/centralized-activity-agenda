# Meeting Notes: Event Submission Flow

## 1) What I changed

- Added a new Information page where organizations can learn how to submit events and contact us.
- Added routing so this page is reachable at /information.
- Added two call-to-action entry points to this page:
  - A footer button.
  - A floating button that stays visible while scrolling and hides near the footer.
- Replaced temporary backend test logic in App with real app layout elements (Footer + floating CTA).
- Updated wording in the navbar to match the new regional focus and corrected spelling consistency.
- Simplified filters on the home page by removing the date dropdown placeholder.
- Simplified event card header area by removing the image element.

## 2) Why I did it this way

- I chose a staged approach:
  - Step 1: Build UX and navigation first.
  - Step 2: Add contact form behavior with frontend validation.
  - Step 3 (later): connect the form to backend endpoint.
- This reduces risk because users can already navigate and submit intent, while backend integration can be done safely after UI/flow validation.
- I used lightweight Vue state (reactive + ref) to keep the form logic simple and readable.
- I used an IntersectionObserver for the floating button so it behaves smartly (no overlap with footer) without expensive scroll listeners.

## 3) Information page logic (easy explanation)

- Form data is stored in one reactive object (name, email, organisation, message).
- Validation runs on submit:
  - Name is required.
  - Email is required and must look valid.
  - Message is required.
- If validation fails, inline error messages are shown.
- If validation passes:
  - Button enters loading state.
  - A temporary async delay simulates API sending.
  - Success message appears.
  - Form fields reset.

## 4) Key talking points for the meeting

- "I moved from a test-only app shell to a real user flow for event onboarding."
- "I added multiple entry points to increase discoverability of event submission."
- "I intentionally separated UI completion from backend integration to ship value sooner with lower risk."
- "The form already has validation and UX states, so plugging in an API is the next small step."

## 5) 30-second version (memorize)

I built the event submission user journey end-to-end on the frontend. There is now a dedicated information page with two onboarding options and a contact form. I exposed this page through routing and added clear CTAs in both the footer and a floating button for visibility. The form includes validation, loading, and success states using simple Vue reactive state. I used a staged approach so we can validate UX now and connect backend submission next.

## 6) If they ask "What is next?"

- Replace the temporary submit delay with a real POST request to backend contact endpoint.
- Add server-side validation and error handling.
- Store submitted requests and optionally trigger notification email.
- Add basic analytics on CTA clicks and form submissions.

## 7) Small known follow-ups

- Footer mobile CSS has a class name typo: .fooetr-inner should be .footer-inner.
- FloatingInfoButton accepts footerRef prop but currently uses document.querySelector("footer"); this can be aligned later.
