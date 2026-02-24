# Chapter 2: Literature Review

This chapter surveys the academic and practitioner literature relevant to the design and evaluation of the Enaam charitable donation platform. The review is organised thematically, progressing from the broader landscape of technology-mediated philanthropy to the specific technical and behavioural design decisions that inform the platform's architecture.

---

## 2.1 The Intersection of Technology and Charitable Giving

Bekkers and Wiepking (2011) analysed over 500 empirical studies and identified eight principal drivers of charitable giving: awareness of need, solicitation, costs and benefits, altruism, reputation, psychological benefits, values, and efficacy. Several of these mechanisms have direct implications for platform design. Awareness of need suggests that presenting real-time campaign progress increases donation likelihood. Costs and benefits highlights the importance of minimising transactional friction. Reputation underscores the value of public recognition features and social proof. Efficacy—the belief that one's contribution makes a tangible difference—demands transparency mechanisms that allow donors to trace their impact.

Despite the operationalisation of these mechanisms by crowdfunding platforms such as GoFundMe and GlobalGiving, most existing platforms address only a subset of the eight drivers. Enaam is designed with explicit attention to all eight: real-time tracking addresses awareness and efficacy; streamlined payment minimises costs; badges and leaderboards engage reputation and psychological benefits; bilingual content connects with values; and notifications operationalise solicitation.

---

## 2.2 Transparency as a Driver of Donor Trust

Saxton and Guo (2011) identified two dimensions of online accountability—disclosure and dialogue—finding that nonprofits were more effective at providing disclosure than creating dialogic engagement. This asymmetry suggests that platforms combining financial reporting with real-time interactive communication may achieve higher donor trust.

Dethier, Delcourt, and Dessart (2024) advanced this conceptualisation with a three-dimensional model of nonprofit transparency: information accessibility, completeness, and accuracy. Their validated instrument demonstrated that donors evaluate these dimensions independently when forming trust judgements.

Enaam addresses all three dimensions. Accessibility is served by dashboards surfacing campaign progress in both Arabic and English. Completeness is achieved through integration of payment confirmations, administrative reports, and campaign statistics. Accuracy is supported by real-time data delivery via the Centrifugo WebSocket server, ensuring donation totals reflect the current system state without polling delays. This continuous, push-based transparency represents a departure from the periodic reporting documented in prior literature.

---

## 2.3 User Experience Design in Non-Profit Platforms

The Nielsen Norman Group's hierarchy of trust identifies five experiential levels of website commitment, from initial visual assessment to sustained engagement (Laubheimer, 2019). At the foundational level, users assess trustworthiness within seconds based on visual design quality and familiar trust indicators. At higher levels, trust depends on interaction reliability and promise fulfilment. This implies that charitable platforms must invest in visual and interactive qualities that establish credibility before users reach the donation form.

Krug (2014) argued that web design should eliminate unnecessary cognitive effort, with particular relevance for charitable platforms where donors span a wide range of technical proficiency. Enaam applies these principles through a minimal-step donation flow, a consistent component library (shadcn/ui), clear visual hierarchy, and prominent progress indicators. Real-time donation notifications via Centrifugo provide social proof, while progress bars convey momentum, drawing on the broader emotional design literature (Norman, 2013).

---

## 2.4 Comparative Analysis of Existing Platforms

**Table 2.1: Comparative Feature Analysis of Charitable Donation Platforms**

| Feature | GoFundMe | GlobalGiving | Ehsan.sa | Enaam |
|---|---|---|---|---|
| Real-time donation tracking | No | No | Limited | Yes (Centrifugo WebSocket) |
| Bilingual support | No | Limited | Arabic only | Full EN + AR with RTL |
| Regional payment gateway | Stripe (Western) | Multiple | Local Saudi | MyFatoorah (MENA) |
| Transparency tools | Basic updates | Periodic reports | Government-audited | Real-time + financial reports |
| Gamification features | No | Badges (orgs) | No | Badges, achievements, leaderboard |
| Open-source codebase | No | No | No | Yes |
| WebSocket integration | No | No | No | Yes (Centrifugo) |

GoFundMe offers broad reach but no real-time tracking, limited transparency, and Western-centric payment infrastructure. GlobalGiving provides accountability through periodic impact reports but lacks a bilingual RTL interface and real-time updates. Ehsan.sa benefits from government-backed trust within Saudi Arabia but is limited to Saudi-registered organisations, lacks gamification, and does not offer real-time WebSocket tracking. Enaam addresses the limitations identified across all three platforms by combining real-time transparency, full bilingual RTL support, regional payment processing, gamification, and an open-source codebase.

---

## 2.5 Modular Architecture in Web Applications

Newman (2015) argued that decomposing systems into independently deployable services enables features to evolve at different rates. Enaam does not implement microservices in the strict sense; the backend is a Laravel monolith. However, the project adopts modular principles: the React frontend communicates with the backend exclusively via REST API; the Vue admin panel operates as a separate SPA; and Centrifugo runs as an independent WebSocket service. Docker Compose provides container-level isolation, offering deployment benefits of service separation without the operational complexity of a fully distributed system. This modular monolith approach reflects a pragmatic assessment of the project's scale.

---

## 2.6 Gamification and Behavioural Design

Fogg (2003) posited that behaviour occurs at the intersection of motivation, ability, and triggers. For donation platforms, this framework implies that narrative and emotional design support motivation, streamlined payment addresses ability, and real-time notifications provide triggers. Golrang and Safari (2021) demonstrated empirically that gamification elements—particularly points, badges, and leaderboards—significantly increased engagement and repeated donation behaviour on crowdfunding platforms.

Enaam implements a multi-layered gamification strategy. Badges reward milestones such as first donation and cumulative thresholds. Progress indicators leverage the goal-gradient effect. Leaderboards introduce social reputation dynamics (Bekkers and Wiepking, 2011). Real-time notifications serve as social proof and solicitation triggers. These features operate in concert to sustain donor participation beyond the initial act of giving.

---

## 2.7 Localisation and Accessibility

The W3C provides comprehensive internationalisation guidelines for RTL scripts (W3C, 2022). RTL support requires document directionality attributes, logical CSS properties, and layout mirroring. Bidirectional text handling is particularly complex when Arabic and English coexist within the same interface. Beyond structural concerns, cultural localisation demands adaptation of date formats, number representations, currency symbols, and progress indicator direction.

Enaam implements full RTL support using CSS logical properties and conditional layout mirroring. The interface is designed with both languages from the outset. Accessibility extends to WCAG 2.1 AA compliance through semantic HTML, keyboard navigability, sufficient colour contrast, and screen reader compatibility via shadcn/ui components.

---

## 2.8 Summary of Gaps in Literature and Practice

**Table 2.2: Gap Analysis Across Literature, Practice, and Enaam**

| Domain | Literature Identifies | Existing Platforms Provide | Enaam Addresses |
|---|---|---|---|
| Donor motivation | Eight mechanisms (Bekkers and Wiepking, 2011) | Partial coverage (2-3 mechanisms) | All eight mechanisms |
| Transparency | Disclosure + dialogue; Accessibility, completeness, accuracy | Periodic retrospective reports | Real-time WebSocket transparency |
| Gamification | MDA framework; Persuasive technology | Minimal or absent | Badges, leaderboards, progress, real-time feedback |
| Architecture | Modular, service-oriented design | Proprietary, non-modular | Open-source, containerised, modular monolith |
| Localisation | W3C RTL and i18n guidelines | English-centric or single-language | Full bilingual EN/AR with RTL |

The central gap that Enaam addresses is the absence of a platform simultaneously providing real-time transparency, comprehensive gamification, full bilingual RTL support, regional payment integration, and an open-source codebase. The literature tends to address these dimensions in isolation; Enaam's design is informed by the proposition that they are interdependent: transparency builds trust, gamification sustains engagement, and well-designed user experience ensures both are accessible to all potential donors.

---

## References

Bekkers, R. and Wiepking, P. (2011) 'A Literature Review of Empirical Studies of Philanthropy: Eight Mechanisms That Drive Charitable Giving', *Nonprofit and Voluntary Sector Quarterly*, 40(5), pp. 924-973. doi: 10.1177/0899764010380927.

Dethier, F., Delcourt, C. and Dessart, L. (2024) 'Donor Perceptions of Nonprofit Organizations' Transparency: Conceptualization and Operationalization', *Nonprofit and Voluntary Sector Quarterly*, 53(2), pp. 487-515. doi: 10.1177/08997640231211212.

Fogg, B.J. (2003) *Persuasive Technology: Using Computers to Change What We Think and Do*. San Francisco: Morgan Kaufmann.

Golrang, H. and Safari, E. (2021) 'Applying Gamification Design to a Donation-Based Crowdfunding Platform for Improving User Engagement', *Entertainment Computing*, 38, 100425. doi: 10.1016/j.entcom.2021.100425.

Krug, S. (2014) *Don't Make Me Think, Revisited: A Common Sense Approach to Web Usability*. 3rd edn. San Francisco: New Riders.

Laubheimer, P. (2019) *The Hierarchy of Trust: Five Experiential Levels of Commitment*. Nielsen Norman Group. Available at: https://www.nngroup.com/articles/commitment-levels/ (Accessed: 15 February 2026).

Newman, S. (2015) *Building Microservices: Designing Fine-Grained Systems*. Sebastopol: O'Reilly Media.

Norman, D.A. (2013) *The Design of Everyday Things*. Revised and expanded edn. New York: Basic Books.

Saudi Press Agency (2024) *Ehsan Platform for Charitable Work Benefits over 4.8 Million People in Saudi Arabia*. Available at: https://spa.gov.sa/en/N2064749 (Accessed: 15 February 2026).

Saxton, G.D. and Guo, C. (2011) 'Accountability Online: Understanding the Web-Based Accountability Practices of Nonprofit Organizations', *Nonprofit and Voluntary Sector Quarterly*, 40(4), pp. 270-295. doi: 10.1177/0899764009341086.

Schell, J. (2019) *The Art of Game Design: A Book of Lenses*. 3rd edn. Boca Raton: CRC Press.

W3C (2022) *Internationalization Best Practices: Handling Right-to-Left Scripts in HTML*. World Wide Web Consortium. Available at: https://www.w3.org/International/questions/qa-html-dir.en.html (Accessed: 15 February 2026).
