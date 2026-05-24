# AGENTS.md - ksfraser/exceptions Library

> **DO NOT MODIFY THIS FILE.** Create `AGENTS.local.md` for project-specific overrides.

## Core Purpose

This is a **shared library** providing centralized exception handling for all ksfraser projects. It contains:
- **Domain exceptions**: Generic business logic exceptions
- **Utility exceptions**: Cross-cutting concerns (validation, parsing, file operations)
- **Module exceptions**: Module-specific exceptions (CRM, Calendar, ProjectManagement, etc.)

---

## Namespace Structure

```
Ksfraser\Exceptions\
├── Domain\           # Generic domain exceptions
│   ├── EntityNotFoundException
│   ├── ConfigurationException
│   └── InvalidBankAccountException
├── Utility\          # Cross-cutting utility exceptions
│   ├── ValidationException
│   ├── FileNotFoundException
│   └── ParsingFailedException
├── FrontAccounting\  # FrontAccounting-specific (FA platform)
├── CRM\              # CRM module exceptions
├── Calendar\          # Calendar module exceptions
└── ProjectManagement\ # ProjectManagement module exceptions
```

---

## Exception Design Guidelines

### Required Properties
```php
class EntityNotFoundException extends \RuntimeException
{
    private string $entityType;
    private mixed $id;

    public static function withId(string $entityType, $id): self
    {
        return new self("{$entityType} not found with id: {$id}");
    }
}
```

### Factory Methods
- Provide factory methods for common instantiation patterns
- Include context information (entity type, IDs, field names)
- Use meaningful error messages with structured data

### Exception Chaining
```php
public function __construct(
    string $message,
    ?\Throwable $previous = null
) {
    parent::__construct($message, 0, $previous);
}
```

### Module-Specific Exceptions

For modules that previously used deep inheritance with type validation in magic methods:

**OLD Pattern (Legacy):**
```php
// In BaseCRM class with magic __set
public function __set($k, $v) {
    validate_type($k, $v);  // Type validation in magic setter
    $this->$k = $v;
    $this->notify("NOTIFY_SET_{$k}", $v);  // Event notification
}
```

**NEW Pattern:**
```php
// Module exception extends library base
use Ksfraser\Exceptions\CRM\CRMException as BaseCRMException;

class CRMException extends BaseCRMException
{
    protected string $debtorNo;
    protected array $context;

    public function __construct(
        string $message, 
        string $debtorNo = '', 
        array $context = [],
        int $code = 0, 
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->debtorNo = $debtorNo;
        $this->context = $context;
    }
    
    public function getDebtorNo(): string { return $this->debtorNo; }
    public function getContext(): array { return $this->context; }
}
```

---

## Coding Standards

### PHP Compatibility
- **Minimum**: PHP 7.3
- Always use `declare(strict_types=1);`

### DocBlock Standards
```php
/**
 * Exception thrown when an entity cannot be found.
 *
 * Used by repository pattern when requested entities don't exist.
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @since 1.0.0
 */
```

### Naming Conventions
- Classes: PascalCase ending with `Exception`
- Files: Match class name (`EntityNotFoundException.php`)
- Factory methods: descriptive names (`withId()`, `forPath()`, `create()`)

---

## Testing Requirements

### Test Structure
```php
namespace Ksfraser\Exceptions\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\Domain\EntityNotFoundException;

class EntityNotFoundExceptionTest extends TestCase
{
    public function testWithIdFactory(): void
    {
        $exception = EntityNotFoundException::withId('User', 'user-123');
        $this->assertEquals('User not found with id: user-123', $exception->getMessage());
    }
}
```

### Coverage Target
- **100% coverage** for all exception classes
- Test all factory methods
- Test exception chaining
- Test getter methods

---

## Version Management

### Semantic Versioning
- **MAJOR**: Incompatible API changes (removing properties, changing signatures)
- **MINOR**: New exception classes (backward compatible)
- **PATCH**: Bug fixes, documentation updates

### Release Tags
```bash
git tag -a v1.3.0 -m "Add module-specific exception hierarchies"
git push origin --tags
```

---

## .gitignore

```
/vendor/
/composer.lock
.phpunit.cache/
.idea/
.vscode/
```

---

## Adding New Exceptions

1. Determine correct namespace (Domain/Utility/Module)
2. Extend appropriate base class
3. Add factory methods for common patterns
4. Include context properties with getters
5. Write comprehensive tests
6. Update documentation

---

## Local Overrides

Create `AGENTS.local.md` for project-specific overrides:

```markdown
# AGENTS.local.md
# Library-specific overrides

[Your overrides here]
```

**Note**: Core exception design principles cannot be overridden.