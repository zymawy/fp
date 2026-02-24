# Chapter 6: Conclusion and References

## 6.1 Summary of Achievements

This project set out to design and implement a full-stack charitable donation platform addressing five research objectives. This section maps each objective to its realised outcome.

**RO1 -- Real-time transparent donations.** The platform delivers real-time donation progress through a Centrifugo WebSocket server integrated with Laravel event broadcasting. When a donation is processed, connected clients see the updated campaign total without requiring a page refresh, directly supporting the transparency principle identified in the literature (Saxton and Guo, 2011; Bekkers and Wiepking, 2011).

**RO2 -- Secure payment gateway integration.** Payment processing uses the MyFatoorah gateway, a provider widely used in the MENA region. Security is enforced through HMAC-SHA256 webhook signature verification, pessimistic database locking via Laravel's `lockForUpdate`, and atomic `raised_amount` updates within a single transaction. These measures collectively ensure accurate donation totals and prevent forged payment confirmations.

**RO3 -- Bilingual English/Arabic interface with RTL support.** Internationalisation was implemented using `react-i18next` with dedicated locale files for English and Arabic. The Tailwind CSS RTL plugin handles automatic layout direction switching, ensuring the entire interface -- including navigation, form layouts, and content flow -- renders correctly for right-to-left scripts in accordance with W3C guidelines (W3C, 2023).

**RO4 -- Role-based access control with separate interfaces.** Authentication is managed through JSON Web Tokens with role-based access control enforced by a dedicated `AdminMiddleware` on the Laravel backend. The architecture separates concerns by providing a React SPA for donors and a Vue 3 dashboard for administrators, both consuming the same RESTful API. This separation aligns with modular service-oriented principles (Newman, 2015) and enables independent development of each interface.

**RO5 -- Automated test suite.** A Pest-based test suite comprising 78 tests with 163 assertions covers authentication, donation processing, cause management, administrative operations, and webhook handling. All tests execute in under one second, supporting rapid feedback during development. The suite proved instrumental in identifying three critical bugs that had not been detected through manual testing.

## 6.2 Critical Reflection

### What Went Well

The modular multi-application architecture was a sound design decision. Separating the donor-facing React application, the Laravel API, and the Vue 3 administrative dashboard into distinct codebases allowed each component to be developed and tested independently, reducing the risk of unintended side effects. Docker Compose proved valuable for managing the multi-service environment declaratively, eliminating configuration inconsistencies. The shared REST API contract between the React and Vue applications naturally encouraged consistent design and comprehensive input validation.

### What Was Challenging

Integrating MyFatoorah with proper webhook security required several iterations, as achieving reliable HMAC-SHA256 verification demanded careful attention to payload format and encoding details not immediately apparent from the gateway documentation. This experience reinforced the importance of treating payment security as a first-class concern. Centrifugo's token-based authentication also presented initial complexity, since debugging silent connection drops in real-time systems is inherently more difficult than diagnosing failures in traditional request-response flows.

## 6.3 Personal Learning

This project deepened understanding of full-stack development across the React and Laravel ecosystems, WebSocket-based real-time communication, payment security patterns, and Docker-based multi-service workflows. Working across three separate applications also provided a concrete appreciation for the coordination challenges that arise in distributed architectures. The most significant insight concerned testing: the discovery of three critical bugs during test suite development demonstrated that writing tests alongside code -- rather than after the fact -- produces meaningfully better results. Tests serve not only as a safety net but as a design tool, forcing careful consideration of expected behaviour, edge cases, and failure modes.

## 6.4 Future Work

- **Alternative payment gateways.** Integrate Stripe and PayPal to extend reach beyond the MENA region using the existing modular payment service architecture.
- **End-to-end frontend tests.** Implement browser-based tests using Playwright or Cypress for critical user journeys: registration, donation, and campaign browsing.
- **Continuous integration and deployment.** Set up a GitHub Actions CI/CD pipeline to automate test execution and deployment on each commit.
- **Load testing.** Establish performance baselines using k6, particularly for WebSocket connections and payment processing endpoints.
- **Gamification frontend completion.** Complete the badge display components and leaderboard views to realise the gamification features described in the design chapter.
- **Push notifications.** Implement notifications for campaign funding milestones to increase donor engagement and transparency.

## 6.5 Concluding Statement

This project demonstrates that a full-stack charitable donation platform can be built with modern web technologies while prioritising transparency, security, and accessibility. The combination of real-time donation updates, secure payment processing, and bilingual support with right-to-left layout addresses genuine gaps in the current charitable giving landscape. While further work remains before production readiness -- particularly in frontend testing, deployment configuration, and payment gateway coverage -- the architectural foundations are sound and the automated test suite provides a reliable basis for continued development.

---

## References

Charities Aid Foundation (2022) *CAF UK Giving Report 2022*. Available at: https://www.cafonline.org/about-us/publications/2022-publications/uk-giving-report-2022 (Accessed: 15 February 2026).

Bekkers, R. and Wiepking, P. (2011) 'A Literature Review of Empirical Studies of Philanthropy: Eight Mechanisms That Drive Giving', *Nonprofit and Voluntary Sector Quarterly*, 40(5), pp. 924--973.

Fielding, R.T. (2000) *Architectural Styles and the Design of Network-based Software Architectures*. Doctoral dissertation. University of California, Irvine. Available at: https://ics.uci.edu/~fielding/pubs/dissertation/top.htm (Accessed: 15 February 2026).

Fogg, B.J. (2003) *Persuasive Technology: Using Computers to Change What We Think and Do*. San Francisco: Morgan Kaufmann.

Hamari, J., Koivisto, J. and Sarsa, H. (2014) 'Does Gamification Work? -- A Literature Review of Empirical Studies on Gamification', *Proceedings of the 47th Hawaii International Conference on System Sciences*. Waikoloa, HI, 6--9 January. IEEE, pp. 3025--3034.

Laravel (2024) *Laravel Documentation*. Available at: https://laravel.com/docs (Accessed: 15 February 2026).

Merkel, D. (2014) 'Docker: Lightweight Linux Containers for Consistent Development and Deployment', *Linux Journal*, 2014(239).

Mollick, E. (2014) 'The Dynamics of Crowdfunding: An Exploratory Study', *Journal of Business Venturing*, 29(1), pp. 1--16.

MyFatoorah (2024) *MyFatoorah Developer Documentation*. Available at: https://docs.myfatoorah.com/ (Accessed: 15 February 2026).

Newman, S. (2015) *Building Microservices: Designing Fine-Grained Systems*. Sebastopol: O'Reilly Media.

Nielsen Norman Group (2019) *Trustworthiness in Web Design: 4 Credibility Factors*. Available at: https://www.nngroup.com/articles/trustworthy-design/ (Accessed: 15 February 2026).

React (2024) *React Documentation*. Available at: https://react.dev/ (Accessed: 15 February 2026).

Sargeant, A. and Woodliffe, L. (2007) 'Building Donor Loyalty: The Antecedents and Role of Commitment in the Context of Charity Giving', *Journal of Nonprofit & Public Sector Marketing*, 18(2), pp. 47--68.

Saxton, G.D. and Guo, C. (2011) 'Accountability Online: Understanding the Web-Based Accountability Practices of Nonprofit Organizations', *Nonprofit and Voluntary Sector Quarterly*, 40(2), pp. 270--295.

Schell, J. (2019) *The Art of Game Design: A Book of Lenses*. 3rd edn. Boca Raton: CRC Press.

World Wide Web Consortium (W3C) (2023) *Web Content Accessibility Guidelines (WCAG) 2.1*. Available at: https://www.w3.org/WAI/WCAG21/quickref/ (Accessed: 15 February 2026).

World Giving Index (2022) *CAF World Giving Index 2022: A Global View of Giving Trends*. Charities Aid Foundation. Available at: https://www.cafonline.org/about-us/publications/2022-publications/caf-world-giving-index-2022 (Accessed: 15 February 2026).

Xu, F., Buhalis, D. and Weber, J. (2017) 'Serious games and the gamification of tourism', *Tourism Management*, 60, pp. 244--256.
