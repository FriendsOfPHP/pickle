# Instructions for the project maintainers

## How to publish a new version?

Simply push a version-like tag, that is a tag like `X.Y.Z` or `vX.Y.Z`, where:

- `X` is the major version
- `Y` is the minor version
- `Z` is the patch version

For example, valid tags are `1.2.3` or `v1.2.3`.

The [`Create draft release`](https://github.com/FriendsOfPHP/pickle/blob/automatic-release-creation/.github/workflows/create-draft-release.yml) GitHub Action will create a **draft** release.
In order to actally publish it:

1. go to the [releases page](https://github.com/FriendsOfPHP/pickle/releases)
2. edit the newly created draft release
3. optionally update the release notes that have been generated automatically
4. publish it
