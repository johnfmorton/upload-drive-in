# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-02-03

### Added
- Multi-cloud storage support (Google Drive and Amazon S3)
- Public upload forms with email validation
- Automatic file organization by submitter email
- Admin dashboard for system configuration and user management
- Employee portal for viewing uploads
- Client upload interface with drag-and-drop support
- Two-factor authentication (2FA) for admin accounts
- Google Drive folder selection and auto-save
- Amazon S3 bucket and folder configuration
- Automatic Google Drive token refresh (scheduled every 6 hours)
- Email notifications for uploads
- Test email feature in setup process
- Privacy policy and terms of service pages
- Setup detection and guided configuration flow

### Security
- Role-based access control (Admin, Employee, Client)
- CSRF protection on all forms
- Email verification for uploads
- Secure token storage for cloud provider credentials
