### 2021-06-08 (3.11.1) ###

* New web service 'Plugins maintenance' available for plugins maintainers to release
  new versions of their plugins.
* Plugins must have well formatted version number (invalid values used to raise
  warning only before).
* If specified, the `$plugin->requires` must be Moodle core version number in the
  form of YYYYMMDDRR or YYYYMMDDRR.XX (invalid values did not raise any warning
  before).
* For plugins that specify `$plugin->requires` only in their version.php, all Moodle
  releases with version number equal or greater than the required one will be
  automatically selected as supported by default.
* The incompatible Moodle branch specified in `$plugin->incompatible` is now validated
  and taken into account when populating the default list of supported Moodle
  versions (all between the required one and the incompatible one).
* Supported Moodle branches specified in `$plugin->supported` are now respected when
  populating the list of supported Moodle versions. Only versions that pass all
  present conditions (supported, requires and incompatible) will be selected.
* It is no longer possible to release a new version that would have the same version
  build number as some already released version. This is intended to avoid having
  different ZIP packages both appearing as the same version which effectively breaks
  other mechanisms in Moodle such as available update notifications.
* When attempting to release a new version that has the version number lower than some
  other already existing plugin version supporting the same Moodle version, a
  validation warning is raised. Without further action (such as hiding the version
  that had existed), the newly added version would have no effect on Moodle branches
  supported by both plugin versions.

### 2020-03-19 ###

* Changing the storage of the precheck smurf files from DB to the file system.

### 2019-03-26 ###

* Remove Bootstrap2 classes and replaced theme with Bootstrap4 equivalents.

### 2019-02-08 (3.6.1) ###

* Added the Translation stats page
* Added the new public Not yet approved report (Mannheim mootde17 devjam suggestion).
* List of previous versions made less prominent (Mannheim mootde17 devjam suggestion).

### 2017-08-18 ###

* Extended the `local_plugins_get_available_plugins` function to provide
  pluginss time last released and versions' time created (MDLSITE-5182).

### 2015-08-27 ###

* Extended the `local_plugins_get_available_plugins` function to provide VCS info (MDLSITE-4149).

### 2015-06-28 ###

* New lightbox implementation for screenshots. Most notably, big images should now scale better and fit the actual user's display
  size.
* Overall cleanup, styling and fixes of the community reviews subsystem.
* New 'Reviewed plugins' public report listing the plugins with a community review.
* New 'Recently approved plugins' report.
* Clean-up of some never fully implemented code (e.g. issues.php)
