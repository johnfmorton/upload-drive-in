# Client File Intake System with Google Drive Integration

## Objective
Build a Laravel-based web application that enables businesses to receive files from their clients directly into their Google Drive accounts. The system will handle uploads via a public-facing form, authenticate and organize client submissions by email, and optionally integrate with Gmail for seamless file request links. While the initial version is for internal use, the architecture should support expansion into a multi-tenant SaaS platform.

---

## Phase 1: MVP for Single User (Sole Proprietor Use)

### Core Features

1. **Public Upload Page with Email Validation**
   - Simple file upload form that requires email validation or a pre-validated token.
   - Fields:
     - Email address (text input)
     - File upload (multiple files supported)
     - Optional message field for additional context
   - Two methods of validation:
     - User manually enters email and completes verification via a one-time code or verification link.
     - User visits the upload page via a special hashed token link (pre-validated).
   - After validation, display the validated email on the upload page.
   - Provide a "Not Me" link to allow users to log out and validate a different email address.

2. **Google Drive Upload**
   - Authenticate a single Google account via OAuth 2.0.
   - Store and refresh access tokens securely.
   - Uploaded files are stored in a designated Google Drive folder.
   - Use or create subfolders named after validated email addresses.

3. **Storage and Logs**
   - Store metadata in a local database:
     - Email address
     - Filename
     - Google Drive file ID
     - Upload timestamp
     - Token status (validated or token-based)
     - Optional message content

4. **Admin Dashboard**
   - Simple dashboard to view uploads, sender emails, validation method, and optional messages.
   - Direct links to the corresponding files or folders in Google Drive.

---

## Phase 2: Gmail Footer Integration (Prototype Feature)

### Goal
Allow users to add a unique upload link in their Gmail signature, pointing clients to the upload page with pre-filled context.
- Example: `https://yourapp.com/upload?to=jane@biz.com`

When visited:
- Associate uploaded files with `jane@biz.com`.
- Route files to a folder named after that recipient.

---

## Phase 3: SaaS Foundation – Multi-Tenant Ready

### Features

1. **Business Account Onboarding**
   - Register business users.
   - Each account connects its own Google Drive via OAuth.
   - Each tenant has isolated metadata and Google Drive storage.

2. **Roles**
   - Admins: Manage settings, integrations, and view all uploads.
   - Staff: View their uploads only.

3. **Settings Per Tenant**
   - Enable/disable email verification.
   - Customize upload link prefix (e.g., `https://mybrandfiles.com/jane`).

---

## Phase 4: Notifications & CMS-Style Dashboard

### Features

1. **Notification System**
   - Email notifications when new files are received.
   - Webhook integrations for Slack or external APIs.

2. **File Inbox**
   - Dashboard with filters:
     - By sender email
     - By upload date
     - By assigned team member (optional)
   - Quick access to corresponding Google Drive folders or files.

---

## Tech Stack
- **Framework:** Laravel 11
- **Frontend:** Blade + Livewire (or Inertia.js with Vue/React)
- **Auth:** Laravel Breeze or Jetstream
- **Database:** MySQL or PostgreSQL
- **Queue:** Redis + Laravel Queue
- **OAuth:** Google API Client for PHP
- **Drive & Gmail APIs:** Google Workspace APIs (OAuth2 Scopes: drive, gmail.compose, etc.)

---

## Key Non-Functional Goals
- Modular codebase built for scaling and multi-tenancy.
- Security around file uploads and token handling.
- Well-documented code and setup instructions.
