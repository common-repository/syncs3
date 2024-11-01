<?php
use Aws\S3\S3Client;

/**
 * Helper function for sending an entry's files to Amazon S3
 *
 * @param  array 	$entry    	Entry data
 * @param  int 		$form_id  	Form ID
 * @param  int 		$field_id 	Field ID
 * @param  array 	$keys     	Keys (access token, secret token, bucket name, and region)
 *
 * @return mixed 	Boolean (false if no files in entry, true if upload is successful), or WP_Error if problem updating entry
 */
function syncs3_send_entry_files_to_s3( $entry, $form_id, $field_id, $keys, $unlink = false ) {

	SyncS3::autoload();

	// Each form has its own upload path and URL
	$upload_path = GFFormsModel::get_upload_path( $form_id );
	$upload_url = GFFormsModel::get_upload_url( $form_id );

	// Loop through these if multi-file upload
	$files = 0 === strpos( $entry[$field_id], '{' ) || 0 === strpos( $entry[$field_id], '[' ) ? json_decode( $entry[$field_id], true ) : $entry[$field_id];

	// Bail if no files
	if ( empty( $files ) ) {
		return;
	}

	// Ensure an array to account for single and multi file uploads
	$files = (array) $files;

	$s3_urls = array();

	$s3 = new S3Client( array(
		'version' => 'latest',
		'region' => $keys['region'],
		'credentials' => array(
			'key' => $keys['access_key'],
			'secret' => $keys['secret_key'],
		),
	) );

	foreach ( $files as $file ) {
		
		// Replace the file URL with the file path
		$file_path = str_replace( $upload_url, $upload_path, $file );

		// Grab the file name
		$file_parts = explode( '/', $file_path );
		$file_name = array_pop( $file_parts );

		// Send the file to S3 bucket
		try {
			$result = $s3->putObject([
				'Bucket' => $keys['bucket_name'],
				'Key'    => "form-{$form_id}/{$entry['id']}/{$file_name}",
				'Body'   => fopen( $file_path, 'r' ),
				'ACL'    => apply_filters( 'syncs3_put_object_acl', 'private', $file, $file_name, $field_id, $form_id, $entry ),
			]);
		} catch (Aws\S3\Exception\S3Exception $e) {
			error_log( "There was an error uploading the file.\n{$e->getMessage()}" );
		}

		if ( ! empty( $result ) ) {

			// Store a reference to the file's S3 URL
			$s3_urls[$field_id][] = array(
				'file_url' => $result['ObjectURL'],
				'key' => "form-{$form_id}/{$entry['id']}/{$file_name}",
				'region' => $keys['region'],
				'bucket' => $keys['bucket_name'],
				'access_key' => $keys['access_key'],
				'secret_key' => $keys['secret_key']
			);
		}
	}

	$existing_urls = gform_get_meta( $entry['id'], 's3_urls' );
	$existing_urls = ! empty( $existing_urls ) ? $existing_urls : array();

	// Store the S3 URLs as entry meta
	return gform_update_meta( $entry['id'], 's3_urls', array_replace( $existing_urls, $s3_urls ) );
}

/**
 * Displays an upgrade box
 *
 * @return void
 */
function syncs3_upgrade_box( $campaign = '' ) {
	?>
	<style>
		.syncs3-upgrade {
			background-color: #fff;
			padding: 30px;
			margin-bottom: 30px;
			border: 1px solid #eee;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			position: relative;
		}
		.syncs3-upgrade:after {
			content: "";
			position: absolute;
			top: 0;
			left: 0;
			height: 100%;
			width: 4px;
			background: radial-gradient(circle at top left,#002dd2 0,#fb3ab6 100%);
		}
		.syncs3-upgrade__cta,
		.syncs3-upgrade__title {
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.syncs3-upgrade__title svg {
			width: 20px;
			margin-right: 10px;
		}
		.syncs3-upgrade__title h2 {
			font-size: 24px !important;
		}
		.syncs3-upgrade .features li::before {
			content: "\f147";
			font-family: dashicons;
			color: #63c384;
			font-size: 20px;
			position: relative;
			top: 4px;
			margin-right: 10px;
		}
		.syncs3-upgrade__features .features {
			display: grid;
			grid-template-columns: 1fr 1fr;
			grid-column-gap: 30px;
			grid-row-gap: 10px;
			align-items: center;
			justify-content: center;
			text-align: left;
			padding: 30px;
		}
		.syncs3-upgrade__features .desc {
			font-size: 18px;
			text-align: center;
		}

		.syncs3-upgrade .upgrade-button {
			color: #fff;
			background-color: #63c384;
			padding: 15px 30px;
			border-radius: 300px;
			text-decoration: none;
		}
	</style>
	<div class="syncs3-upgrade">
		<div class="syncs3-upgrade__title">
			<svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 55 55" xmlns="http://www.w3.org/2000/svg"><g fill="#e15343" fill-rule="nonzero" transform="translate(-22.641 -21.806)"><path d="m49.995 21.806c15.113 0 27.363 2.877 27.363 6.424 0 3.562-12.25 6.431-27.363 6.431-15.105 0-27.354-2.868-27.354-6.431.001-3.547 12.249-6.424 27.354-6.424z"/><path d="m49.995 39.095c14.088 0 25.684-2.49 27.193-5.707l-8.947 38.199c0 2.372-8.166 4.28-18.246 4.28-10.07 0-18.234-1.908-18.234-4.28l-8.949-38.199c1.511 3.216 13.105 5.707 27.183 5.707z"/></g></svg>
			<h2>Upgrade to SyncS3 Pro</h2>
		</div>
		<div class="syncs3-upgrade__features">
			<p class="desc">Need more from SyncS3? Access even more, enhanced features to integrate your Gravity Forms with Amazon S3.</p>
			<ul class="features">
				<li>Push file uploads to any Amazon S3 account or bucket.</li>
				<li>Set different accounts and buckets <strong>per field</strong>.</li>
				<li>Remove files from your local server after they're uploaded to S3.</li>
				<li>Upload existing files from previous entries in bulk using the WP CLI.</li>
				<li>Make quick work of migrating entries with files without breaking file paths.</li>
				<li>Priority support.</li>
			</ul>
		</div>
		<div class="syncs3-upgrade__cta">
			<a href="https://elegantmodules.com/modules/syncs3-gravity-forms/?utm_source=syncs3_lite&utm_medium=banner&utm_campaign=<?php echo $campaign; ?>&utm_content=syncs3_upgrade" class="upgrade-button">Upgrade to SyncS3 Pro</a>
		</div>
	</div>
	<?php
}

/**
 * Retrieves the correct AWS Access Key.
 * 
 * @since 1.1.0
 *
 * @param  array   	$form  		Form data
 * @param  object 	$field 		Field object
 *
 * @return string   Access key
 */
function syncs3_get_aws_access_key( $form = array(), $field = false ) {

	$key = '';
	$settings = get_option( 'gravityformsaddon_syncs3_settings' );

	// Global key
	$global_key = ! empty( $settings['amazons3_access_key'] ) ? $settings['amazons3_access_key'] : '';

	// Form-level key
	$form_meta = ! empty( $form['id'] ) ? RGFormsModel::get_form_meta( $form['id'] ) : array();
	$form_key = ! empty( $form_meta['syncs3']['amazons3_access_key'] ) ? $form_meta['syncs3']['amazons3_access_key'] : '';

	if ( ! empty( $field->type ) && 'fileupload' === $field->type && ! empty( $field->amazonS3AccessKeyField ) && ! empty( $field->amazonS3SecretKeyField ) ) {
		// Use field-level key
		$key = $field->amazonS3AccessKeyField;
	} else if ( ! empty( $form_key ) ) {
		// Use form-level key
		$key = $form_key;
	} else if ( ! empty( $global_key ) ) {
		// Use global key
		$key = $global_key;
	}

	return $key;
}

/**
 * Retrieves the correct AWS Secret Key.
 * 
 * @since 1.1.0
 *
 * @param  array   	$form  		Form data
 * @param  object 	$field 		Field object
 *
 * @return string 	Secret key
 */
function syncs3_get_aws_secret_key( $form = array(), $field = false ) {

	$key = '';
	$settings = get_option( 'gravityformsaddon_syncs3_settings' );

	// Global key
	$global_key = ! empty( $settings['amazons3_secret_key'] ) ? $settings['amazons3_secret_key'] : '';

	// Form-level key
	$form_meta = ! empty( $form['id'] ) ? RGFormsModel::get_form_meta( $form['id'] ) : array();
	$form_key = ! empty( $form_meta['syncs3']['amazons3_secret_key'] ) ? $form_meta['syncs3']['amazons3_secret_key'] : '';

	if ( ! empty( $field->type ) && 'fileupload' === $field->type && ! empty( $field->amazonS3AccessKeyField ) && ! empty( $field->amazonS3SecretKeyField ) ) {
		// Use field-level key
		$key = $field->amazonS3SecretKeyField;
	} else if ( ! empty( $form_key ) ) {
		// Use form-level key
		$key = $form_key;
	} else if ( ! empty( $global_key ) ) {
		// Use global key
		$key = $global_key;
	}

	return $key;
}

/**
 * Retrieves the correct AWS Bucket name.
 * 
 * @since 1.1.0
 *
 * @param  array   	$form  		Form data
 * @param  object 	$field 		Field object
 *
 * @return string 	Secret key
 */
function syncs3_get_aws_bucket_name( $form = array(), $field = false ) {

	$key = '';
	$settings = get_option( 'gravityformsaddon_syncs3_settings' );

	// Global key
	$global_bucket = ! empty( $settings['amazons3_bucket_name'] ) ? $settings['amazons3_bucket_name'] : '';

	// Form-level key
	$form_meta = ! empty( $form['id'] ) ? RGFormsModel::get_form_meta( $form['id'] ) : array();
	$form_bucket = ! empty( $form_meta['syncs3']['amazons3_bucket_name'] ) ? $form_meta['syncs3']['amazons3_bucket_name'] : '';

	if ( ! empty( $field->type ) && 'fileupload' === $field->type && ! empty( $field->amazonS3BucketNameField ) ) {
		// Use field-level key
		$key = $field->amazonS3BucketNameField;
	} else if ( ! empty( $form_bucket ) ) {
		// Use form-level key
		$key = $form_bucket;
	} else if ( ! empty( $global_bucket ) ) {
		// Use global key
		$key = $global_bucket;
	}

	return $key;
}

/**
 * Retrieves the correct AWS region.
 * 
 * @since 1.1.0
 *
 * @param  array   	$form  		Form data
 * @param  object 	$field 		Field object
 *
 * @return string 	Secret key
 */
function syncs3_get_aws_region( $form = array(), $field = false ) {

	$key = '';
	$settings = get_option( 'gravityformsaddon_syncs3_settings' );

	// Global key
	$global_region = ! empty( $settings['amazons3_region'] ) ? $settings['amazons3_region'] : '';

	// Form-level key
	$form_meta = ! empty( $form['id'] ) ? RGFormsModel::get_form_meta( $form['id'] ) : array();
	$form_region = ! empty( $form_meta['syncs3']['amazons3_region'] ) ? $form_meta['syncs3']['amazons3_region'] : '';

	if ( ! empty( $field->type ) && 'fileupload' === $field->type && ! empty( $field->amazonS3RegionField ) ) {
		// Use field-level key
		$key = $field->amazonS3RegionField;
	} else if ( ! empty( $form_region ) ) {
		// Use form-level key
		$key = $form_region;
	} else if ( ! empty( $global_region ) ) {
		// Use global key
		$key = $global_region;
	}

	return $key;
}

/**
 * Retrieves the full AWS config (keys, region, and bucket).
 * 
 * @since 1.1.0
 *
 * @param  array   	$form  		Form data
 * @param  object 	$field 		Field object
 *
 * @return string 	Secret key
 */
function syncs3_get_aws_keys( $form = array(), $field = false ) {
	return array(
		'access_key' => syncs3_get_aws_access_key( $form, $field ),
		'secret_key' => syncs3_get_aws_secret_key( $form, $field ),
		'bucket_name' => syncs3_get_aws_bucket_name( $form, $field ),
		'region' => syncs3_get_aws_region( $form, $field ),
	);
}

/**
 * Parses an S3 URL.
 *
 * @param  string 	$url 	S3 URL
 *
 * @return array
 */
function syncs3_get_url_parts( $url ) {

	$the_parts = explode( '/', str_replace( 'https://', '', $url ) );

	// The first part of the URL contains the bucket name and region
	$s3parts = explode( '.', $the_parts[0] );
	$bucket = $s3parts[0];
	$region = $s3parts[2];
	$file_name = array_pop( $the_parts );

	return array(
		'file_name' => $file_name,
		'bucket' => $bucket,
		'region' => $region
	);
}