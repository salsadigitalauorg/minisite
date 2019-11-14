The "Minisite" module
=====================
Provides the ability to upload arbitrary 'minisites' to a Drupal website.

[![CircleCI](https://circleci.com/gh/salsadigitalauorg/minisite.svg?style=svg&circle-token=fd691d0f6736c1fb3e232c4d5f7d3fcd3fd12524)](https://circleci.com/gh/salsadigitalauorg/minisite)

# Heads up!
The module has actively been refactored, so try to avoid using it in production
until refactoring is finished.

## Roadmap
~~1. Add CI config (code linting, testing, deployment to D.O.)~~
~~2. Make module installable in CI~~
~~3. Add fixture creating test helpers to work with dynamic fixture archives 
     during tests~~
~~4. Add valid end-to-end UI test to nake sure that straightforward 
     configuration works.~~
  5. Add other tests:
    - Use node alias as minisite path prefix (not clear how to serve from root 
      of the site).
    - Show file description (or whatever other field we can use) to replace the 
      file name with this label value on node view.
    - Validation: invalid archive format
    - Validation: invalid archive size
    - Validation: invalid archive contents - not allowed files
    - Validation: invalid archive contents - invalid structure (no top-level) 
  6. Fix coding standards.
  7. Remove obsolete and stale code.
  8. Assess and simplify config schema, field names etc.
  9. Refactor procedural code in to classes.
  10. Enable deployment to D.O.
  11. Check that D.O. testbot passes all tests.
  12. Submit for formal review by providing a patch on D.O.
  13. Merge patch and make a stable release.

Instructions
------------
This module provides the ability to upload static 'minisites' to a Drupal 
website and maintain the minisite's look and feel. Please note this module does 
not actually import the minisite pages as Drupal nodes.

> A minisite is a website by which companies offer information about one 
specific product or product group. Typically, a minisite is enhanced by 
various multimedia content, such as an animated, narrated introduction, 
and accompanied by a visual scheme which complements the product 
well. - [Minisite - Wikipedia Features](https://en.wikipedia.org/wiki/Minisite)

Installation and configuration
------------------------------
https://www.drupal.org/project/minisite

Attention
---------
Strongly suggest that only allow trusted user upload minisite archive file. 
And use antivirus software to detect malicious software, including viruses. 
You may check this module [ClamAV](https://www.clamav.net/) which will verify 
that files uploaded to a site are not infected with a virus, and prevent 
infected files from being saved.
