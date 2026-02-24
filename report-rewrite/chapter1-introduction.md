# Chapter 1: Introduction

## 1.1 Project Overview

Enaam is a full-stack web-based charitable donation platform designed to address deficiencies in transparency, accessibility, and donor engagement within online philanthropic giving, with particular emphasis on the Middle East and North Africa (MENA) region. The platform name, derived from the Arabic word meaning "blessings," reflects its core objective: facilitating trustworthy and culturally appropriate digital charitable giving.

The contemporary charitable giving ecosystem is characterised by a significant trust deficit. Donors encounter opaque reporting mechanisms, limited visibility into fund allocation, and insufficient feedback on campaign progress. These shortcomings are compounded in the MENA region by a scarcity of platforms offering comprehensive Arabic language support, right-to-left (RTL) layout adaptation, and integration with regionally prevalent payment infrastructure.

Enaam addresses these challenges through three principal components: a donor-facing single-page application (SPA) built with React 18 and TypeScript, an administrative dashboard constructed with Vue 3, and a RESTful API implemented in Laravel 12. The system is orchestrated through Docker Compose, with real-time campaign updates delivered via a Centrifugo WebSocket server. The platform further incorporates bilingual English/Arabic support with full RTL adaptation, secure payment processing through the MyFatoorah gateway, and a gamification layer of badges and achievement milestones.

## 1.2 Motivation

Global online giving increased by approximately 21% between 2019 and 2021 (Charities Aid Foundation, 2022), yet this growth has introduced concerns regarding transparency, accountability, and equitable treatment of diverse donor populations. Prominent platforms such as GoFundMe offer broad reach but limited post-donation transparency and no real-time tracking. GlobalGiving provides accountability mechanisms but remains oriented towards English-speaking Western markets. Within the MENA region, Ehsan.sa offers government-backed verification and Arabic support but lacks gamification features, real-time feedback mechanisms, and availability to non-Saudi organisations.

A critical gap exists across these platforms: the absence of a unified solution simultaneously addressing transparency through real-time progress tracking, cultural accessibility through comprehensive bilingual and RTL support, and sustained engagement through gamification. This gap is particularly acute for Arabic-speaking donors, who represent a philanthropically active demographic—charitable giving constitutes one of the five pillars of Islam, and the MENA region consistently ranks among the most generous globally in per-capita contributions (World Giving Index, 2022)—yet are poorly served by predominantly English-language digital giving infrastructure.

The motivation for this project arose from direct observation of these deficiencies: recurring patterns of donor hesitation attributable to insufficient transparency, frustration with inadequate Arabic language support, and disengagement resulting from impersonal donation workflows.

## 1.3 Research Objectives

Five research objectives guide the design, implementation, and evaluation of Enaam:

**RO1: Design and implement a transparent donation platform with real-time progress tracking.** This addresses the trust deficit through real-time campaign progress indicators and donation activity feeds, leveraging a Centrifugo WebSocket server for instantaneous updates.

**RO2: Integrate a secure payment gateway supporting multiple MENA-region payment methods.** The platform integrates MyFatoorah, supporting Visa, Mastercard, KNET, Apple Pay, and regional bank transfers, with secure webhook-based transaction verification.

**RO3: Implement bilingual support for English and Arabic with full RTL layout adaptation.** The donor-facing application employs react-i18next with dynamic layout direction switching, culturally appropriate formatting, and comprehensive translation coverage.

**RO4: Develop role-based access control with separate donor and administrative interfaces.** The donor SPA and Vue 3 admin dashboard serve distinct user roles, with JWT-based authentication and role-specific permission scoping.

**RO5: Create a comprehensive automated test suite to validate platform reliability.** The test suite encompasses unit and integration tests covering authentication, donation processing, payment verification, and role-based access control.

## 1.4 Project Scope

**Inclusions:**
- Donor-facing React 18 SPA with campaign browsing, donations, and real-time tracking
- Vue 3 administrative dashboard for campaign and user management
- Laravel 12 RESTful API with JWT authentication
- Centrifugo WebSocket server for real-time updates
- MyFatoorah payment gateway integration
- Badge and achievement gamification system
- Full English/Arabic bilingual support with RTL layout
- Docker Compose containerised deployment

**Exclusions:**
- Native mobile applications (responsive web only)
- Blockchain-based auditing (transparency via real-time tracking instead)
- Multi-currency conversion
- Production deployment configuration
