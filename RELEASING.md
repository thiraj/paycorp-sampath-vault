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

### End-of-life framework versions in CI

Laravel 8 through 11 are end of life with unpatched advisories, so Composer will
not install them unless `policy.advisories.block` is set to false. The matrix
rows for those versions carry `eol-framework: true` and relax the policy for
themselves only; the `coverage` job keeps it enabled. Never relax it for a
release build.

### Laravel 5.5 – 5.8: the `legacy-bootstrap` job

Those versions cannot be reached through testbench: testbench 3.5 pins
`phpunit ^6.5`, while the suite needs assertions added in `phpunit` 9.1. That is
an incompatibility between two dev tools, not a limitation of the package — so
rather than carry `^5.5` in the constraint as an unverified claim, the
`legacy-bootstrap` job installs `laravel/framework` at 5.5 and 5.8 with the dev
block **removed** and runs `tests/Legacy/bootstrap-check.php`, which:

- registers the service provider against a real `Illuminate\Container\Container`
  and asserts `mergeConfigFrom()` landed the shipped defaults;
- resolves every container binding and the facade;
- re-signs all 90 frozen golden HMAC vectors and compares each digest.

The vector comparison is the assertion that matters. A signature that differs on
an old framework is a payment the gateway rejects, and no amount of green unit
tests elsewhere would catch it.

Two details in that job are load-bearing, and both cost an afternoon to
rediscover:

- **`composer remove --dev`, not `composer update --no-dev`.** `--no-dev` still
  *resolves* `require-dev`, and `orchestra/testbench` pulls `laravel/framework`,
  which `replaces` `illuminate/*`. Leaving the dev block in place fails with
  `laravel/framework replaces illuminate/view and thus cannot coexist with it`
  and nothing resolves at all.
- **`laravel/framework`, not the `illuminate/*` split packages.** The shipped
  config file calls `env()`, which lives in `illuminate/support`'s helpers but
  depends on `vlucas/phpdotenv` and `phpoption/phpoption` — brought in only by
  `laravel/framework`. Installing `illuminate/support` alone dies at
  `mergeConfigFrom()` with `Class 'PhpOption\Option' not found`. That is a
  harness artifact, not a package defect; a real application always has the
  framework.

Laravel 5.5 also needs `allow-plugins.kylekatarnls/update-helper false`, because
`nesbot/carbon` 1.x ships a composer plugin that modern composer blocks.

If you ever drop `^5.5` from the constraint, delete those two matrix rows in the
same commit — and remember that dropping a version is a **major** release.

### Testbench to Laravel mapping

| Laravel | Testbench | PHP floor |
|---|---|---|
| 5.5 – 5.8 | none — see below | 7.3 |
| 6 | `^4.0` | 7.3 |
| 7 | `^5.0` | 7.3 |
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

# 2. No credential may ever reappear in the tree.
#    The structural canary is part of the suite -- it fails if any config value
#    grows a literal, a host name, or an env() default:
vendor/bin/phpunit --filter testThePublishedConfigFileContainsNoHardCodedCredentials

#    Plus a generic scan for secret-shaped literals anywhere in the shipped code.
#    No leaked value is written down here: printing it in this file would
#    re-disclose it on every clone.
git grep -nIE "'[A-Za-z0-9+/]{16,}'|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}" \
  -- src config

# 3. Confirm the distributed archive is minimal
composer archive --format=tar --dir=/tmp
tar -tf /tmp/paycorp-sampath-vault-*.tar | grep -E 'tests/|\.github/|examples/' \
  && echo 'export-ignore is not working' || echo 'archive is clean'

# 4. The legacy half of the support range, without testbench
php tests/Legacy/bootstrap-check.php

# 5. Set the version constant and the changelog date
#    src/PaycorpSampathVault.php  -> const VERSION
#    CHANGELOG.md                 -> replace "unreleased" with the date
#    (the User-Agent derives from the constant; do not hardcode it)

# 6. Tag and push
git tag -a v2.0.0 -m 'v2.0.0 — Laravel 5.5-13, security fixes, test suite'
git push origin 2.x
git push origin v2.0.0
```

Packagist picks the tag up via its GitHub hook. Verify afterwards that the new
version is listed and that its `require` block shows the full Laravel range.

## Ship a prerelease before a major

2.x rewrote the signing and transport path: `CurlTransport`, `Sha256HmacSigner`
and `Latin1Encoder` are the code that produces the bytes the bank verifies. The
golden vectors prove byte-equality with 1.x offline, which is strong — but it is
not the same as a live gateway accepting a live signature.

So before the first stable tag of any major, publish a prerelease and put it
through a real merchant account against the Paycorp **sandbox**:

```bash
git tag -a v2.0.0-beta.1 -m 'v2.0.0-beta.1 — gateway verification pending'
git push origin v2.0.0-beta.1
```

Composer treats `-beta.N` as unstable, so no consumer requiring `^2.0` picks it
up by accident; a tester opts in with `"^2.0@beta"`. Exercise every flow that
signs a request — hosted redirect, real-time payment, and store / retrieve /
verify / delete token — then tag the stable release. `examples/smoke-test.php`
is the starting point.

Do not skip this because the suite is green. The suite cannot tell you that the
bank agrees with it.

## Do not retag

Never move or delete a published tag. Composer and Packagist cache resolved
tags, and for a payment package a silently changed `v2.0.0` is indefensible.
Release `v2.0.1` instead.

## Keep `v1.4` in place

Leave the 1.x tags published. Deleting them would break `composer install` for
every application with a 1.x lock file, and it would not remediate the leaked
credentials — those are already mirrored. Rotation is the remediation; the
disclosure in `SECURITY.md` is the record.
