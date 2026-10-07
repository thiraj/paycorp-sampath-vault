# Releasing

## Tagging strategy: one tag, every Laravel version

**There is deliberately no tag per Laravel version.** A consumer requires
`^2.0` and Composer resolves it against whatever framework version they are on:

```json
"require": { "createch/paycorp-sampath-vault": "^2.0" }
```

| Tag | PHP | Laravel covered by that single tag |
|---|---|---|
| `v2.0.0` | 7.3 – 8.4 | 5.5 · 6 · 7 · 8 · 9 · 10 · 11 · 12 · 13 |

This works because the package depends on only three framework APIs —
`ServiceProvider`, `Facade`, and the config repository — none of which has
changed across that range. The breadth lives in one Composer constraint:

```json
"illuminate/support": "^5.5 || ^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0"
```

### Why not a tag per framework version

Per-framework tags (`v2.0-laravel10`, `v2.0-laravel11`, …) are an anti-pattern
for a package this thin:

- Composer already solves it. A branch per framework version means N branches to
  backport every security fix into, and this is a payment package — a fix that
  lands on four of five branches is a liability.
- Packagist resolves by constraint, not by tag name. A consumer on Laravel 11
  gets the right code from `^2.0` automatically.
- The audit trail fragments. One `v2.0.1` is one CHANGELOG entry and one CVE
  reference; five parallel tags are five.

Split the line **only** when a framework version forces a genuinely
incompatible implementation. That has not happened in 5.5 → 13.

## When a new Laravel version ships

1. Add it to the `illuminate/support` constraint in `composer.json`.
2. Add a matrix row to `.github/workflows/tests.yml` (see the testbench mapping
   below).
3. If the suite passes, release a **patch** — it is a compatibility addition, not
   a feature.
4. Update the compatibility table in `README.md`.

### Testbench to Laravel mapping

| Laravel | Testbench | PHP floor |
|---|---|---|
| 8 | `^6.0` | 7.3 |
| 9 | `^7.0` | 8.0 |
| 10 | `^8.0` | 8.1 |
| 11 | `^9.0` | 8.2 |
| 12 | `^10.0` | 8.2 |
| 13 | `^11.0` | 8.3 |

## Version policy

- **Patch** (`2.0.x`) — bug fixes, security fixes, support for a newly released
  Laravel or PHP version.
- **Minor** (`2.x.0`) — new methods or config keys, all additive.
- **Major** (`3.0.0`) — anything that changes the signed request bytes, removes a
  deprecated entry point, raises the PHP floor, or converts the static
  pseudo-enums to real PHP enums.

Dropping a Laravel version from the constraint is a **major** release. There is no
reason to drop any yet: supporting 5.5 costs one constraint entry.

## Release checklist

```bash
# 1. Every gate green
vendor/bin/phpunit
phpstan analyse -c phpstan.neon.dist
phpstan analyse -c phpstan-strict.neon.dist
composer validate --strict

# 2. No credential may ever reappear in the tree
git grep -nE '0uKyQ562Rf2Q7jbk|a62cdd4d-c882|YkBb7uqHROKCUOB6|ad0862e6-13a2' -- \
  ':!SECURITY.md' ':!README.md' ':!UPGRADE.md' ':!CHANGELOG.md' ':!tests/Integration/ServiceProviderTest.php'
# (the excluded files name them intentionally: disclosure docs and a canary test)

# 3. Confirm the distributed archive is minimal
composer archive --format=tar --dir=/tmp
tar -tf /tmp/paycorp-sampath-vault-*.tar | grep -E 'tests/|\.github/|examples/' \
  && echo 'export-ignore is not working' || echo 'archive is clean'

# 4. Set the version constant and the changelog date
#    src/PaycorpSampathVault.php  -> const VERSION
#    CHANGELOG.md                 -> replace "unreleased" with the date

# 5. Tag and push
git tag -a v2.0.0 -m 'v2.0.0 — Laravel 5.5-13, security fixes, test suite'
git push origin 2.x
git push origin v2.0.0
```

Packagist picks the tag up via its GitHub hook. Verify afterwards that the new
version is listed and that its `require` block shows the full Laravel range.

## Do not retag

Never move or delete a published tag. Composer and Packagist cache resolved
tags, and for a payment package a silently changed `v2.0.0` is indefensible.
Release `v2.0.1` instead.

## Keep `v1.4` in place

Leave the 1.x tags published. Deleting them would break `composer install` for
every application with a 1.x lock file, and it would not remediate the leaked
credentials — those are already mirrored. Rotation is the remediation; the
disclosure in `SECURITY.md` is the record.
