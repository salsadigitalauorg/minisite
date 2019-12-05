Minisite
========
Drupal 8 module that allows to upload and serve 'minisite' files.

[![CircleCI](https://circleci.com/gh/salsadigitalauorg/minisite.svg?style=svg&circle-token=fd691d0f6736c1fb3e232c4d5f7d3fcd3fd12524)](https://circleci.com/gh/salsadigitalauorg/minisite)

About
-----
Allows to upload and archive with a 'minisite' files and serve it from a 
Drupal website, while maintaining the minisite's look and feel. 

The pages can be served from the location in the public or private files 
directory or from an alias set on the parent entity (where the field is attached
to). 

Please note that this module does not actually import the minisite pages as 
Drupal nodes.

> A minisite is a website by which companies offer information about one 
specific product or product group. Typically, a minisite is enhanced by 
various multimedia content, such as an animated, narrated introduction, 
and accompanied by a visual scheme which complements the product 
well. - [Minisite - Wikipedia Features](https://en.wikipedia.org/wiki/Minisite)

Supported archives
------------------
ZIP, TAR

Archive structure
-----------------
- Must have a single root directory.
- Must have index.html file inside of the root directory.
- Must have files only with allowed extensions (configured per-field).
- May have documents with absolute or relative path in links, scripts etc.

A shell script `fix-archives.sh` is provided to automatically check and cleanup
multiple zip archives.

1. Create a directory, say `data`, and place all your zip files there.
2. Run `./fix-archives.sh data data_fixed`\.
3. Fixed ZIPs will be saved to `data_fixed` directory.

Any directories that does not follow the archive structure will trigger an error
and stop processing. You would need to resolve any issues with those archives
manually and re-try the script again.

Attention
---------
Only allow trusted user upload minisite archive file. Also, use antivirus 
software to detect malicious software, including viruses. 
Check out [ClamAV](https://www.drupal.org/project/clamav) module which will 
verify that files uploaded to a site are not infected with a virus, and prevent
infected files from being saved. It will also prevent uploading a 
[ZIP-bomb](https://en.wikipedia.org/wiki/Zip_bomb) also known as a "ZIP of 
death or decompression bomb".

User Reports
------------
Please post to the [issue queue](https://www.drupal.org/project/issues/minisite) 
to help make the module better. Feel free to provide patches and suggestions 
too.

Local module development
------------------------

### Build
Run `.circleci/build.sh` to start inbuilt PHP server locally and run the same
commands as in CI, plus installing a site and your module automatically.

### Code linting
Run `.circleci/lint.sh` to lint your code according to the 
[Drupal coding standards](https://www.drupal.org/docs/develop/standards).

### Tests
Run `.circleci/test.sh` to run all test for your module.         

### Browsing SQLite database
To browse the content of created SQLite database 
(located at `/tmp/site_[MODULE_NAME].sqlite`), use [DB Browser for SQLite](https://sqlitebrowser.org/).
