<?php

/**
 * @file
 * Minisite default template.
 *
 * Variables:
 * - $minisite: Raw html of minisite.
 *
 * @see minisite_theme_registry_alter()
 * @see minisite_preprocess_html()
 *
 * * @ingroup themeable
 */

?>

<?php if (!empty($minisite)): ?>
  <?php print $minisite; ?>
<?php endif; ?>
