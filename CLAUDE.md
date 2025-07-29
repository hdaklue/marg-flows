# KluePortal Development Session

## Planning Strategy
- Always stick to the current core logic and improve on
- Only suggest major changes in case of security risk and/or memory leaks, infinite loop requesting etc.
- Always think about the edge cases as well
- Always get the best output, using minimal tokens
- Prevent rabbit holes as possible as you can

## Project Overview
KluePortal is a sophisticated Laravel application implementing enterprise-level flow and task management with advanced role-based access control (RBAC) and multi-tenant architecture.

## Current Architecture
- **Multi-tenant RBAC** with 1-hour caching (226ms response, 5MB memory)
- **Dual-database approach**: Main DB (core) + Business DB (supplementary)
- **DTOs**: TaskDto, AssigneeDto under `app/DTOs/`
- **Enums**: AssigneeRole (ASSIGNEE, APPROVER, REVIEWER, OBSERVER)
- **Test user**: test@example.com / password (admin on 5 tenants)

## Commands
```bash
php artisan db:seed --class=DatabaseSeeder  # Performance testing
```

## Development Guidelines
- Use Hero icons for SVGs
- Apply dark/light mode (`.dark` class)
- Use Alpine event syntax (`@`, `.stop`, `.prevent`, `.window`)
- Use `zinc` instead of `gray`, `sky` instead of `blue`
- Use `x-tooltip` and `x-anchor` for dropdowns
- Use ULIDs and `getMorphClass()` for morphs
- Use `Relation::getMorphAlias` and `getMorphedModel()`

## Available Libraries (Context7)
- **Laravel Actions** (`/lorisleiva/laravel-actions-docs`) - Single-purpose action classes with controller/job/command/listener support
- **Laravel Soulbscription** (`/lucasdotvin/laravel-soulbscription`) - Subscription and feature consumption management

## Local Documentation
- **Laravel Validated DTO** (`laravel-validated-dto-docs.md`) - Data Transfer Objects with validation for Laravel applications