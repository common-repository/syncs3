=== SyncS3 Lite ===
Contributors: elegantmodules
Tags: Gravity Forms, Amazon, S3, files, sync
Tested up to: 5.3
Stable tag: 1.1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Push and sync Gravity Forms file uploads to your Amazon S3 buckets.

== Description ==

SyncS3 gives Gravity Forms users the ability to push any files to any Amazon S3 bucket. When files are submitted through a form, you can send those files to any Amazon account, and any S3 bucket. Simply add your Amazon AWS credentials, chose which fields should push to S3, and save.

You can even send different file-upload fields to different accounts or buckets. SyncS3 gives you flexible control over which accounts/buckets for sending your files by controlling the settings on global, form, and field levels.

If it doesn't make sense for you to also store the files locally, you can set the files to be removed from your server after they're uploaded to S3. This helps reduce the overall disk space of your website.

SyncS3 also includes a WP CLI command for processing all entries, so you can effortlessly send all of your files from all of your form entries to your S3 buckets.

Want to know more about SyncS3? Read about [SyncS3's features](https://elegantmodules.com/modules/syncs3-gravity-forms/?utm_source=syncs3_lite&utm_medium=link&utm_campaign=readme&utm_content=syncs3_upgrade).

== Installation ==

This section describes how to install the plugin and get it working.

### Automatically

1. Search for SyncS3 in the Add New Plugin section of the WordPress admin
2. Install & Activate

### Manually

1. Download the zip file and upload `syncs3-lite` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How do I use this plugin? =

First, install and activate the plugin.

Next, configure your global Amazon AWS settings. These are found in your wp-admin, under Forms --> Settings. This is where you'll enter your AWS Access Key, Secret Key, Region, and Bucket name.

If you more control over which accounts files are sent to, [SyncS3 Pro](https://elegantmodules.com/modules/syncs3-gravity-forms/?utm_source=syncs3_lite&utm_medium=link&utm_campaign=readme&utm_content=syncs3_upgrade) allows you to send different file upload fields to different accounts or buckets.

Finally, open the Gravity Forms editor for the file-upload field you want to push to S3. In the Advanced tab, enable the Uploads to S3 field setting, and update your form. SyncS3 will now push files submitted via that field to your Amazon S3 bucket when a user submits the form.

= Do you offer support for this plugin? =

If you have any questions or need any help, please get in touch with us on [our website](https://elegantmodules.com/modules/syncs3-gravity-forms/?utm_source=syncs3_lite&utm_medium=link&utm_campaign=readme&utm_content=syncs3_upgrade).

== Screenshots ==

1. SyncS3 is an add-on for Gravity Forms. Enter your Amazon AWS credentials to connect your S3 account, and select a bucket to host files that are uploaded through your forms. In [SyncS3 Pro](https://elegantmodules.com/modules/syncs3-gravity-forms/?utm_source=syncs3_lite&utm_medium=link&utm_campaign=readme&utm_content=syncs3_upgrade), you can set different accounts and buckets per *form*, or per *field*!
2. SyncS3 file-upload field settings to enable a field to upload files to Amazon S3. Fields do not upload to S3 by default, so you can pick and chose which fields do.
3. In the entry's details, you have links to the files that are now hosted on Amazon S3.

== Changelog ==

= 1.1.2 =
* Fixed inconsistency with how S3 URLs were saved.

= 1.1.1 =
* Added entry-specific folder to S3 path to help avoid filename conflicts

= 1.1.0 =
* Adjusted how S3 URLs are saved as entry meta
* Fixed issue with multi-file uploads
* Added presigned URLs in the entry's admin and `{s3urls}` merge tag
* Refactored how AWS keys, bucket, and region are retrieved. This makes each variable individually determined, which allows for overwriting a single value (e.g. overwriting just the bucket name for a single field).
* Switched `SyncS3Addon->process_entry()` from `gform_after_submission` to `gform_entry_created`. The former occurs too late in the process, which wouldn't allow for using the S3 URLs in the {s3urls} merge tag.
* Cleaned up field settings display

= 1.0.2 =
* Switched `SyncS3Addon->process_entry()` from `gform_after_submission` to `gform_entry_created`. The former occurs too late in the process, which wouldn't allow for using the S3 URLs in the {s3urls} merge tag.
* Style tweaks

= 1.0.1 =
* Fixed issue causing uploads to not send

= 1.0 =
* Initial version