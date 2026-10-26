# MedCore Health Systems - Security Architecture & Hardening Guide

## Executive Security Posture

MedCore Health Systems operates a mission-critical Electronic Health Record (EHR) and Medical Appointments API. Given the sensitive nature of clinical data and Protected Health Information (PHI), the application is engineered under the principle of **Defense in Depth** and zero-trust data access.

This platform adheres strictly to the **Health Insurance Portability and Accountability Act (HIPAA) Security Rule** (45 CFR Part 160 and Part 164, Subparts A and C) and implements proactive defenses against the **OWASP API Security Top 10 (2023 / 2026)**.

### HIPAA Security Rule Principles

1. **Confidentiality (§ 164.312(a)(1) - Access Control & Encryption)**
   - Strict access governance prevents unauthorized viewing of electronic Protected Health Information (ePHI).
   - Dynamic role-based and object-level authorization guarantees that only treating physicians, authorized clinical personnel, and authenticated patients can inspect specific health records.
   - All PHI fields are encrypted both at rest and in transit.

2. **Integrity (§ 164.312(c)(1) - Integrity Controls)**
   - Cryptographic hashing, database transactions, and strict type casting ensure that clinical records, lab results, and appointment schedules cannot be modified or altered without detection.
   - Append-only audit logging tracks all mutations with immutable records.

3. **Availability (§ 164.312(b) - Transmission & System Resiliency)**
   - High-availability container architecture running on hardened Alpine Linux with clustered PostgreSQL 16 and Redis 7.
   - Rate limiting, denial-of-service (DoS) mitigations, and worker memory caps guarantee continuous uptime for emergency clinical operations.

---

## OWASP API Security Top 10 Compliance Matrix

| OWASP API Risk | Architectural Defense in MedCore EHR API |
| :--- | :--- |
| **API1:2023 Broken Object Level Authorization (BOLA / IDOR)** | UUIDv7 primary keys across all clinical entities; explicit Laravel `Policies` (`AppointmentPolicy`, `MedicalRecordPolicy`) on every endpoint; route model binding strictly scoped to tenant and relationship contexts. |
| **API2:2023 Broken Authentication** | Laravel Sanctum v4 scoped tokens with cryptographically secure generation, SHA-256 token hashing, strict time-to-live (TTL), and IP/User-Agent fingerprint validation. |
| **API3:2023 Broken Object Property Level Authorization** | Dedicated Laravel Form Request classes with strict `validated()` payload extraction. Mass-assignment protection via `$fillable` allowlists; medical diagnosis and internal clinical fields cannot be updated by patients. |
| **API4:2023 Unrestricted Resource Consumption** | Granular Redis-backed rate limiting (`throttle:api`, `throttle:auth`, `throttle:appointments`), pagination boundaries (`max: 100` items), and Nginx upload buffer clamps (`client_max_body_size 10M`). |
| **API5:2023 Broken Function Level Authorization (BFLA)** | Multi-tier Role-Based Access Control (`spatie/laravel-permission`) separating `admin`, `doctor`, and `patient` capabilities at the route middleware level. |
| **API6:2023 Unrestricted Access to Sensitive Business Flows** | Anti-automation throttles on appointment reservations, slot locking mechanisms via Redis distributed locks to prevent double-booking, and transactional SMS/Email verification. |
| **API7:2023 Server-Side Request Forgery (SSRF)** | Outbound clinical webhook integrations and external laboratory API calls pass through isolated HTTP client proxies with private IP (RFC 1918 / RFC 3927) filtering and DNS rebinding protections. |
| **API8:2023 Security Misconfiguration** | Hardened PHP 8.5 Alpine images with disabled administrative functions, `APP_DEBUG=false` in non-local environments, strict CORS headers, and automated security header injection. |
| **API9:2023 Improper Inventory Management** | OpenAPI 3.1 documentation generated automatically from code; versioned API routes (`/api/v1/...`); strict decommissioning of legacy endpoints. |
| **API10:2023 Unsafe Consumption of APIs** | Strict validation of responses received from third-party healthcare partners (insurers, labs) using explicit schema assertions before storing into the EHR database. |

---

## CVE Mitigation Matrix (2024–2026)

The MedCore EHR API runs on **Laravel 13** and **PHP 8.5 (Alpine Linux)**. The following vulnerability matrix details mitigations for critical modern PHP ecosystem CVEs:

### 1. CVE-2026-109618 & CVE-2026-109619: PHP Core Multipart/OOM Memory Corruption ("php2shell")
- **Vulnerability Impact**: Memory corruption and remote code execution caused by boundary overflows and unconstrained memory allocation during multipart form-data parsing in legacy PHP runtimes.
- **Mitigation Implemented**:
  - Production base image pinned to **PHP 8.5.12+** on Alpine Linux with patched C-standard multipart parser libraries.
  - Strict upload bounds enforced at both the Nginx reverse proxy (`client_max_body_size 10M;`) and PHP engine (`post_max_size = 12M`, `upload_max_filesize = 10M`).
  - PHP-FPM process isolation with worker recycling: `pm.max_requests = 500` ensures workers are terminated and memory is completely reclaimed after handling batches of requests, preventing heap manipulation.

### 2. CVE-2024-52301: Laravel `register_argc_argv` Environment Manipulation
- **Vulnerability Impact**: Exploitation of `register_argc_argv = On` allowing query-string arguments to populate `$argv`, leading to environment manipulation and configuration tampering in older Laravel applications.
- **Mitigation Implemented**:
  - Enforced `register_argc_argv = Off` in `docker/php/99-hardened.ini`.
  - Built on **Laravel 13**, which incorporates a hardened `EnvironmentDetector` that discards raw CLI argument arrays when running in web SAPI (`fpm-fcgi`) contexts.

### 3. CVE-2024-4577: PHP-CGI Argument Injection
- **Vulnerability Impact**: Best-fit character mapping bypass in Windows PHP-CGI runtimes that allowed attackers to inject command-line arguments (such as `-d allow_url_include=1`) into PHP child processes.
- **Mitigation Implemented**:
  - The API runs exclusively on **Linux (Alpine containerized)** under **PHP-FPM**.
  - CGI execution is disabled: `cgi.fix_pathinfo = 0` and FastCGI wrapper limits execution to `.php` files via `security.limit_extensions = .php`. The PHP-CGI binary is absent from deployment containers.

### 4. CVE-2025-27515 & CVE-2026-114509: File Validation Bypass & TokenGuard Type Coercion
- **Vulnerability Impact**: Wildcard pattern validation bypass allowing arbitrary file upload extensions, combined with loose scalar comparisons in authentication token resolvers leading to session takeover.
- **Mitigation Implemented**:
  - **Laravel 13.x Core**: Utilizes unified MIME inspection via `finfo` byte inspection rather than relying solely on file extensions or client headers.
  - **Strict Types**: Every file in the repository enforces `declare(strict_types=1);`.
  - **Sanctum v4 Hardening**: Sanctum token authentication uses cryptographically secure string comparison (`hash_equals()`) with strict type assertions, preventing type-juggling vulnerabilities.

### 5. SQL Injection via Dynamic Column Identifiers
- **Vulnerability Impact**: While Eloquent parameterizes SQL values, user-supplied column names in `ORDER BY` and `WHERE` clauses cannot be bound via standard PDO parameters, exposing APIs to identifier injection.
- **Mitigation Implemented**:
  - Runtime allowlisting on all query sorting and filtering endpoints using Laravel's `Rule::in()`:
  ```php
  // Example implementation in AppointmentQueryRequest
  public function rules(): array
  {
      return [
          'sort_by' => ['sometimes', 'string', Rule::in(Appointment::ALLOWED_SORT_COLUMNS)],
          'order'   => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
      ];
  }
  ```

### 6. Insecure Deserialization & Gadget Chains
- **Vulnerability Impact**: Object injection via untrusted PHP `unserialize()` allowing remote code execution via POP (Property Oriented Programming) gadget chains.
- **Mitigation Implemented**:
  - Native **JSON-only session serialization** in Laravel 13 (`SESSION_SERIALIZATION=json`).
  - Redis-backed sessions and queues (`SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`) where payloads are strictly stored as JSON strings.
  - Complete elimination of native PHP `unserialize()` across all application code, job handlers, and service integrations.

---

## Healthcare Data Protection (PHI & ePHI)

```
+-----------------------------------------------------------------------------------+
|                         MedCore EHR Security Architecture                         |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [ Client / Mobile App / Web Portal ]                                             |
|                     |                                                             |
|                     | HTTPS (TLS 1.3) + Sanctum Scoped Bearer Token               |
|                     v                                                             |
|  [ Nginx Hardened Reverse Proxy ] -> ModSecurity WAF + Rate Limiting              |
|                     |                                                             |
|                     v                                                             |
|  [ Laravel 13 (PHP 8.5 Alpine) ]                                                  |
|     |-- Strict Types (`declare(strict_types=1)`)                                  |
|     |-- Form Requests (Input Validation & Sort Column Allowlist)                  |
|     |-- RBAC Middleware (`spatie/laravel-permission`)                             |
|     |-- Policies (`AppointmentPolicy`, `MedicalRecordPolicy` -> BOLA Protection)  |
|     |-- Eloquent Models (AES-256 Encrypted Casts for PHI Fields)                  |
|     |-- Audit Observer (`AuditLogService` -> Immutable Audit Trail)               |
|                     |                                                             |
|          +----------+----------+                                                  |
|          |                     |                                                  |
|          v                     v                                                  |
|  [ PostgreSQL 16 ]     [ Redis 7 Cluster ]                                        |
|  - ePHI AES-256        - Token Cache                                              |
|  - UUIDv7 Identifiers  - JSON Sessions & Queues                                   |
|  - Append-Only Audits  - Slot Locking                                             |
+-----------------------------------------------------------------------------------+
```

### 1. Encryption at Rest (AES-256-CBC / GCM)
Under HIPAA § 164.312(a)(2)(iv), Protected Health Information stored in databases must be rendered unreadable to unauthorized entities.

MedCore Health Systems implements transparent column-level encryption via Eloquent's native `encrypted` attribute casts. Clinical notes, diagnoses, and medical profiles are encrypted prior to database insertion and decrypted only when hydrated into authorized Eloquent models:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasUuids;

    protected $casts = [
        'diagnosis'        => 'encrypted',
        'clinical_notes'   => 'encrypted',
        'treatment_plan'   => 'encrypted',
        'recorded_at'      => 'datetime',
    ];
}
```

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    use HasUuids;

    protected $casts = [
        'allergies'        => 'encrypted:array',
        'medical_history'  => 'encrypted',
        'emergency_contact'=> 'encrypted:array',
    ];
}
```

- **Key Management**: Application encryption keys are stored securely in environment variables supplied via runtime secrets management (e.g., HashiCorp Vault / AWS Secrets Manager). Keys are never committed to version control.

### 2. Object-Level Authorization (BOLA / IDOR Prevention)
- **Non-Sequential Identifiers**: All database tables use **UUIDv7** primary keys. This prevents enumeration attacks where an attacker increments an integer ID (`/api/v1/medical-records/1042` -> `1043`) to discover other patients' records.
- **Strict Policy Gates**: Endpoints invoking clinical entities must pass through explicit Laravel Policies before executing:

```php
namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    public function view(User $user, MedicalRecord $record): bool
    {
        // Admins with clinical compliance role
        if ($user->hasRole('admin')) {
            return true;
        }

        // The patient who owns this record
        if ($user->hasRole('patient') && $user->id === $record->patient->user_id) {
            return true;
        }

        // Treating doctor assigned to the patient or consultation
        if ($user->hasRole('doctor') && $record->doctor_id === $user->id) {
            return true;
        }

        return false;
    }
}
```

### 3. Immutable Clinical Audit Trail (`audit_logs`)
HIPAA § 164.312(b) demands audit controls that record and examine activity in information systems containing or using ePHI.

The MedCore EHR API maintains an append-only `audit_logs` table. Every creation, retrieval, modification, or export of clinical data is logged synchronously or via reliable Redis queues:

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `UUIDv7` | Primary key of the audit entry |
| `user_id` | `UUIDv7` | ID of the authenticated user performing the action |
| `action` | `VARCHAR(50)` | Action verb (`CREATE`, `READ_PHI`, `UPDATE`, `DELETE`, `EXPORT`) |
| `auditable_type` | `VARCHAR(100)` | Target entity class (`App\Models\MedicalRecord`) |
| `auditable_id` | `UUIDv7` | Target entity identifier |
| `ip_address` | `INET` | Client IPv4 / IPv6 address |
| `user_agent` | `TEXT` | Client HTTP User-Agent string |
| `old_values` | `JSONB` | Previous state (sanitized and scrubbed of raw secrets) |
| `new_values` | `JSONB` | Updated state (sanitized and scrubbed of raw secrets) |
| `created_at` | `TIMESTAMPTZ` | Microsecond-accurate timestamp of the event |

Database user privileges for the application account prevent `UPDATE` or `DELETE` queries on the `audit_logs` table, ensuring immutability against insider threats and application vulnerabilities.

---

## Security Best Practices for Engineers

1. **Strict Type Declarations**: Every new PHP file must include `declare(strict_types=1);` as the first statement.
2. **Never Query Raw Input**: Always use Eloquent query methods or parameterized raw bindings (`DB::raw` with explicit bindings). Never concatenate variables into SQL strings.
3. **Validate All Inputs**: Create dedicated `FormRequest` classes for every route. Always call `$request->validated()` instead of `$request->all()`.
4. **Enforce Policy Checks**: Use `$this->authorize('action', $model)` or route middleware `can:action,model` on every controller method handling patient or appointment data.
5. **No Secrets in Code**: Environment secrets must be defined in `.env` and injected via CI/CD secret vaults. Never commit credentials, private keys, or API tokens.

---

## Vulnerability Disclosure & Reporting

MedCore Health Systems welcomes responsible disclosure of potential security vulnerabilities.

If you believe you have discovered a vulnerability affecting our API or patient data:
- **Email**: `security@medcore-ehr.com`
- **PGP Key ID**: `0xMEDC04E133742` (Available upon request or via standard keyservers)
- **Response SLA**: Initial triage within **24 hours**; remediation timeline established within **72 hours**.
- **Coordinated Disclosure**: Please refrain from publicly disclosing or sharing details with third parties until our engineering team has deployed a patch to protect patient confidentiality.
