# Copilot instructions

## Project

- This repository is a Symfony 8 REST API for GSB scheduling.
- Primary stack: PHP 8.4+, Doctrine ORM 3, PostgreSQL, Symfony Security and Lexik JWT.
- API modules use JSON controllers, DTOs, service interfaces, repositories and PHPUnit.

## Architecture

- Keep request mapping and validation in `src/Dto/*Input.php`.
- Keep JSON response shapes in `src/Dto/*Output.php`.
- Controllers in `src/Controller/` must delegate business operations to service interfaces.
- Put business rules in `src/Service/Impl/`; define contracts in `src/Service/Contract/`.
- Put persistence queries in `src/Repository/Impl/` or `src/Repository/` implementations and define contracts in `src/Repository/Contract/`.
- Keep entities focused on state and invariants. Use UUID entities and immutable Doctrine date types consistently.
- Use domain exceptions for expected conflicts and not-found cases.

## Tests

- Follow test-first development: add or update a focused PHPUnit test before implementation.
- Unit tests belong in `tests/Service/` and mock repository/service dependencies.
- Functional tests belong in `tests/Controller/` and verify status codes and JSON response shapes.
- Authentication is JWT-backed. Reuse the existing login/register helpers in controller tests.
- Run the smallest relevant test file first, then run the related suite:
  - `php bin/phpunit tests/Service/WeekServiceTest.php`
  - `php bin/phpunit tests/Controller/WeekControllerTest.php`

## Domain rules

- `Week` is uniquely identified by `(year, weekNumber)`.
- A `Week` owns one or more `Shift` entities; shifts must remain linked to an existing week.
- A year must be between 2000 and 2100 and an ISO week number must be between 1 and 53.
- Start dates use `Y-m-d`.
- Preserve the UUID, account-link and soft-delete behavior of existing employee modules.

## Symfony conventions

- Add routes with PHP route attributes and `Requirement::UUID` for UUID route parameters.
- Use `#[MapRequestPayload]` for JSON DTO mapping.
- All `/api/*` endpoints are stateless and protected by JWT unless explicitly public.
- Use `JsonResponse` for API controllers and Symfony HTTP status constants.
- Keep `/auth/login` and `/auth/register` behavior aligned with the existing security configuration.

## Database and migrations

- PostgreSQL is the production database.
- After changing Doctrine mappings, create and review a Doctrine migration.
- Never edit generated migrations manually except to provide intentional SQL or fix generated SQL.
- Do not assume PostgreSQL-specific behavior in unit tests.

## Validation

- Run `php bin/console lint:container`.
- Run `php bin/console debug:router` when changing routes.
- Run focused PHPUnit tests after implementation.
- Preserve existing public JSON field names and HTTP status behavior unless a change is explicitly requested.
