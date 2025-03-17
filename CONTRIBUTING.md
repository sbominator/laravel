# Contributing

## Code of Conduct

All members of the project community must abide by the [Contributor Covenant, version 2.1](CODE_OF_CONDUCT.md).
Only by respecting each other we can develop a productive, collaborative community.
Instances of abusive, harassing, or otherwise unacceptable behavior may be reported by contacting [a project maintainer](.reuse/dep5).

## Committing

Our commit messages use a simplified form of [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/). This is how our automated release system knows what a given commit means.

```md
<type>: <description>

[body]
```

### Commit type prefixes

The `type` can be any of `feat`, `fix` or `chore`.

The prefix is used to calculate the semver release level, and the section of the release notes to place the commit message in.

| **type**   | When to Use                          | Release Level | Release Note Section  |
| ---------- | ----------------------------------- | ------------- | --------------------   |
| feat       | A feature has been added            | `minor`       | **Added**           |
| fix        | A bug has been patched              | `patch`       | **Fixed**          |
| deps        | Changes to the dependencies          | `patch`       | **Changed**          |
| perf       | Performance improvements            | none          | **Performance Improvements**   |
| chore      | Any changes that aren't user-facing | none          | none                   |
| docs       | Documentation updates               | none          | none                   |
| style      | Code style and formatting changes   | none          | none                   |
| refactor   | Code refactoring                    | none          | none                   |                |
| test       | Adding tests or test-related changes| none          | none                   |
| build      | Build system or tooling changes     | none          | none                   |
| ci         | Continuous Integration/Deployment    | none          | none                   |
| revert     | Reverting a previous commit          | none          | none                   |
| wip        | Work in progress (temporary)        | none          | none                   |
