<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\UserInterface;

/**
 * Tests the minisite field creation through UI.
 *
 * @group file
 */
class MinisiteUiTest extends MinisiteTestBase {

  use CommentTestTrait;
  use FieldUiTestTrait;

  /**
   * Tests upload and remove buttons for a single-valued Minisite field.
   */
  public function testSingleValuedWidget() {
    $type_name = $this->contentType;

    $name = 'ms_' . strtolower($this->randomMachineName());
    $label = $this->randomMachineName();

    // Create field through UI.
    $storage_edit = [
      'settings[uri_scheme]' => 'public',
      'settings[target_type]' => 'file',
      'settings[display_field]' => '',
      'settings[display_default]' => '',
    ];
    // @todo: popiulate this
    $field_edit = [

    ];

    // -----------------
    // -----------------
    // -----------------

    $field_type = 'minisite';
    $field_name = $name;
    $bundle_path ="admin/structure/types/manage/$type_name";

    $initial_edit = [
      'new_storage_type' => $field_type,
      'label' => $label,
      'field_name' => $field_name,
    ];

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
    }

    // First step: 'Add field' page.
    $this->drupalPostForm($bundle_path, $initial_edit, t('Save and continue'));
    $this->assertRaw(t('These settings apply to the %label field everywhere it is used.', ['%label' => $label]), 'Storage settings page was displayed.');
//    // Test Breadcrumbs.
//    $this->assertLink($label, 0, 'Field label is correct in the breadcrumb of the storage settings page.');
//
//    // Second step: 'Storage settings' form.
//    $this->drupalPostForm(NULL, $storage_edit, t('Save field settings'));
//    $this->assertRaw(t('Updated field %label field settings.', ['%label' => $label]), 'Redirected to field settings page.');




    //$this->fieldUIAddNewField("admin/structure/types/manage/$type_name", $name, $label, 'minisite', $storage_edit, $field_edit);

    // Modify field settings.
    //
    //
    //    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    //    $type_name = $this->contentType;
    //    $field_name = strtolower($this->randomMachineName());
    //    $this->createMinisite($field_name, 'node', $type_name);
    //
    //    $test_file = $this->getTestFile('text');
    //
    //    // Create a new node with the uploaded file and ensure it got uploaded
    //    // successfully.
    //    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    //    $node = $node_storage->loadUnchanged($nid);
    //    $node_file = File::load($node->{$field_name}->target_id);
    //    $this->assertFileExists($node_file, 'New file saved to disk on node creation.');
    //
    //    // Ensure the file can be downloaded.
    //    $this->drupalGet($node_file->createFileUrl());
    //    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
    //
    //    // Ensure the edit page has a remove button instead of an upload button.
    //    $this->drupalGet("node/$nid/edit");
    //    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Upload'), 'Node with file does not display the "Upload" button.');
    //    $this->assertFieldByXpath('//input[@type="submit"]', t('Remove'), 'Node with file displays the "Remove" button.');
    //    $this->drupalPostForm(NULL, [], t('Remove'));
    //
    //    // Ensure the page now has an upload button instead of a remove button.
    //    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), 'After clicking the "Remove" button, it is no longer displayed.');
    //    $this->assertFieldByXpath('//input[@type="submit"]', t('Upload'), 'After clicking the "Remove" button, the "Upload" button is displayed.');
    //    // Test label has correct 'for' attribute.
    //    $input = $this->xpath('//input[@name="files[' . $field_name . '_0]"]');
    //    $label = $this->xpath('//label[@for="' . $input[0]->getAttribute('id') . '"]');
    //    $this->assertTrue(isset($label[0]), 'Label for upload found.');
    //
    //    // Save the node and ensure it does not have the file.
    //    $this->drupalPostForm(NULL, [], t('Save'));
    //    $node = $node_storage->loadUnchanged($nid);
    //    $this->assertTrue(empty($node->{$field_name}->target_id), 'File was successfully removed from the node.');
  }

  /**
   * Tests upload and remove buttons for multiple multi-valued Minisite fields.
   */
  public function te1stMultiValuedWidget() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    // Use explicit names instead of random names for those fields, because of a
    // bug in drupalPostForm() with multiple file uploads in one form, where the
    // order of uploads depends on the order in which the upload elements are
    // added to the $form (which, in the current implementation of
    // FileStorage::listAll(), comes down to the alphabetical order on field
    // names).
    $field_name = 'test_file_field_1';
    $field_name2 = 'test_file_field_2';
    $cardinality = 3;
    $this->createMinisite($field_name, 'node', $type_name, ['cardinality' => $cardinality]);
    $this->createMinisite($field_name2, 'node', $type_name, ['cardinality' => $cardinality]);

    $test_file = $this->getTestFile('text');

    // Visit the node creation form, and upload 3 files for each field. Since
    // the field has cardinality of 3, ensure the "Upload" button is displayed
    // until after the 3rd file, and after that, isn't displayed. Because
    // SimpleTest triggers the last button with a given name, so upload to the
    // second field first.
    $this->drupalGet("node/add/$type_name");
    foreach ([$field_name2, $field_name] as $each_field_name) {
      for ($delta = 0; $delta < 3; $delta++) {
        $edit = ['files[' . $each_field_name . '_' . $delta . '][]' => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
        // If the Upload button doesn't exist, drupalPostForm() will
        // automatically fail with an assertion message.
        $this->drupalPostForm(NULL, $edit, t('Upload'));
      }
    }
    $this->assertNoFieldByXpath('//input[@type="submit"]', t('Upload'), 'After uploading 3 files for each field, the "Upload" button is no longer displayed.');

    $num_expected_remove_buttons = 6;

    foreach ([$field_name, $field_name2] as $current_field_name) {
      // How many uploaded files for the current field are remaining.
      $remaining = 3;
      // Test clicking each "Remove" button. For extra robustness, test them out
      // of sequential order. They are 0-indexed, and get renumbered after each
      // iteration, so array(1, 1, 0) means:
      // - First remove the 2nd file.
      // - Then remove what is then the 2nd file (was originally the 3rd file).
      // - Then remove the first file.
      foreach ([1, 1, 0] as $delta) {
        // Ensure we have the expected number of Remove buttons, and that they
        // are numbered sequentially.
        $buttons = $this->xpath('//input[@type="submit" and @value="Remove"]');
        $this->assertTrue(is_array($buttons) && count($buttons) === $num_expected_remove_buttons, format_string('There are %n "Remove" buttons displayed.', ['%n' => $num_expected_remove_buttons]));
        foreach ($buttons as $i => $button) {
          $key = $i >= $remaining ? $i - $remaining : $i;
          $check_field_name = $field_name2;
          if ($current_field_name == $field_name && $i < $remaining) {
            $check_field_name = $field_name;
          }

          $this->assertIdentical($button->getAttribute('name'), $check_field_name . '_' . $key . '_remove_button');
        }

        // "Click" the remove button (emulating either a nojs or js submission).
        $button_name = $current_field_name . '_' . $delta . '_remove_button';
        $this->getSession()->getPage()->findButton($button_name)->press();
        $num_expected_remove_buttons--;
        $remaining--;

        // Ensure an "Upload" button for the current field is displayed with the
        // correct name.
        $upload_button_name = $current_field_name . '_' . $remaining . '_upload_button';
        $buttons = $this->xpath('//input[@type="submit" and @value="Upload" and @name=:name]', [':name' => $upload_button_name]);
        $this->assertTrue(is_array($buttons) && count($buttons) == 1, 'The upload button is displayed with the correct name.');

        // Ensure only at most one button per field is displayed.
        $buttons = $this->xpath('//input[@type="submit" and @value="Upload"]');
        $expected = $current_field_name == $field_name ? 1 : 2;
        $this->assertTrue(is_array($buttons) && count($buttons) == $expected, 'After removing a file, only one "Upload" button for each possible field is displayed.');
      }
    }

    // Ensure the page now has no Remove buttons.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), 'After removing all files, there is no "Remove" button displayed.');

    // Save the node and ensure it does not have any files.
    $this->drupalPostForm(NULL, ['title[0][value]' => $this->randomMachineName()], t('Save'));
    preg_match('/node\/([0-9])/', $this->getUrl(), $matches);
    $nid = $matches[1];
    $node = $node_storage->loadUnchanged($nid);
    $this->assertTrue(empty($node->{$field_name}->target_id), 'Node was successfully saved without any files.');

    // Try to upload more files than allowed on revision.
    $upload_files_node_revision = [$test_file, $test_file, $test_file, $test_file];
    foreach ($upload_files_node_revision as $i => $file) {
      $edit['files[test_file_field_1_0][' . $i . ']'] = \Drupal::service('file_system')->realpath($test_file->getFileUri());
    }

    // @todo: Replace after https://www.drupal.org/project/drupal/issues/2917885
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldExists('files[test_file_field_1_0][]');
    $submit_xpath = $this->assertSession()->buttonExists('Save')->getXpath();
    $client = $this->getSession()->getDriver()->getClient();
    $form = $client->getCrawler()->filterXPath($submit_xpath)->form();
    $client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $edit);

    $node = $node_storage->loadUnchanged($nid);
    $this->assertEqual(count($node->{$field_name}), $cardinality, 'More files than allowed could not be saved to node.');

    $upload_files_node_creation = [$test_file, $test_file];
    // Try to upload multiple files, but fewer than the maximum.
    $nid = $this->uploadNodeFiles($upload_files_node_creation, $field_name, $type_name, TRUE, []);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertEqual(count($node->{$field_name}), count($upload_files_node_creation), 'Node was successfully saved with multiple files.');

    // Try to upload exactly the allowed number of files on revision.
    $this->uploadNodeFile($test_file, $field_name, $node->id(), 1, [], TRUE);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertEqual(count($node->{$field_name}), $cardinality, 'Node was successfully revised to maximum number of files.');

    // Try to upload exactly the allowed number of files, new node.
    $upload_files = [$test_file, $test_file, $test_file];
    $nid = $this->uploadNodeFiles($upload_files, $field_name, $type_name, TRUE, []);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertEqual(count($node->{$field_name}), $cardinality, 'Node was successfully saved with maximum number of files.');
  }

  /**
   * Tests a minisite field with a "Private files" upload destination setting.
   *
   * @todo: Review this.
   */
  public function te1stPrivateFileSetting() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Grant the admin user required permissions.
    user_role_grant_permissions($this->adminUser->roles[0]->target_id, ['administer node fields']);

    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createMinisite($field_name, 'node', $type_name);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();

    $test_file = $this->getTestFile('text');

    // Change the field setting to make its files private, and upload a file.
    $edit = ['settings[uri_scheme]' => 'private'];
    $this->drupalPostForm("admin/structure/types/manage/$type_name/fields/$field_id/storage", $edit, t('Save field settings'));
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node = $node_storage->loadUnchanged($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'New file saved to disk on node creation.');

    // Ensure the private file is available to the user who uploaded it.
    $this->drupalGet($node_file->createFileUrl());
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');

    // Ensure we can't change 'uri_scheme' field settings while there are some
    // entities with uploaded files.
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id/storage");
    $this->assertFieldByXpath('//input[@id="edit-settings-uri-scheme-public" and @disabled="disabled"]', 'public', 'Upload destination setting disabled.');

    // Delete node and confirm that setting could be changed.
    $node->delete();
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id/storage");
    $this->assertFieldByXpath('//input[@id="edit-settings-uri-scheme-public" and not(@disabled)]', 'public', 'Upload destination setting enabled.');
  }

  /**
   * Tests validation with the Upload button.
   */
  public function t1estWidgetValidation() {
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createMinisite($field_name, 'node', $type_name);
    $this->updateMinisite($field_name, $type_name, ['file_extensions' => 'txt']);

    $type = 'nojs';
    // Create node and prepare files for upload.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $nid = $node->id();
    $this->drupalGet("node/$nid/edit");
    $test_file_text = $this->getTestFile('text');
    $test_file_image = $this->getTestFile('image');
    $name = 'files[' . $field_name . '_0]';

    // Upload file with incorrect extension, check for validation error.
    $edit[$name] = \Drupal::service('file_system')->realpath($test_file_image->getFileUri());
    $this->drupalPostForm(NULL, $edit, t('Upload'));

    $error_message = t('Only files with the following extensions are allowed: %files-allowed.', ['%files-allowed' => 'txt']);
    $this->assertRaw($error_message, t('Validation error when file with wrong extension uploaded (JSMode=%type).', ['%type' => $type]));

    // Upload file with correct extension, check that error message is removed.
    $edit[$name] = \Drupal::service('file_system')->realpath($test_file_text->getFileUri());
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $this->assertNoRaw($error_message, t('Validation error removed when file with correct extension uploaded (JSMode=%type).', ['%type' => $type]));
  }

  /**
   * Tests file widget element.
   *
   * @todo: Not sure if we need this.
   */
  public function t1estWidgetElement() {
    $field_name = mb_strtolower($this->randomMachineName());
    $html_name = str_replace('_', '-', $field_name);
    $this->createMinisite($field_name, 'node', 'article', ['cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED]);
    $file = $this->getTestFile('text');
    $xpath = "//details[@data-drupal-selector='edit-$html_name']/div[@class='details-wrapper']/table";

    $this->drupalGet('node/add/article');

    $elements = $this->xpath($xpath);

    // If the field has no item, the table should not be visible.
    $this->assertIdentical(count($elements), 0);

    // Upload a file.
    $edit['files[' . $field_name . '_0][]'] = $this->container->get('file_system')->realpath($file->getFileUri());
    $this->drupalPostForm(NULL, $edit, "{$field_name}_0_upload_button");

    $elements = $this->xpath($xpath);

    // If the field has at least a item, the table should be visible.
    $this->assertIdentical(count($elements), 1);

    // Test for AJAX error when using progress bar on minisite field widget.
    $http_client = $this->getHttpClient();
    $key = $this->randomMachineName();
    $post_request = $http_client->request('POST', $this->buildUrl('file/progress/' . $key), [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertNotEquals(500, $post_request->getStatusCode());
    $body = Json::decode($post_request->getBody());
    $this->assertContains('Starting upload...', $body['message']);
  }

  /**
   * Creates a temporary file, for a specific user.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param \Drupal\user\UserInterface $user
   *   The user of the file owner.
   *
   * @return \Drupal\file\FileInterface
   *   A file object, or FALSE on error.
   */
  protected function createTemporaryFile($data, UserInterface $user = NULL) {
    $file = file_save_data($data, NULL, NULL);

    if ($file) {
      if ($user) {
        $file->setOwner($user);
      }
      else {
        $file->setOwner($this->adminUser);
      }
      // Change the file status to be temporary.
      $file->setTemporary();
      // Save the changes.
      $file->save();
    }

    return $file;
  }
}
