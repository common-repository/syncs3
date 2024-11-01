<?php
use Aws\S3\S3Client;

GFForms::include_addon_framework();

class SyncS3Addon extends GFAddOn {

	protected $_version = SYNCS3_VERSION;
	protected $_min_gravityforms_version = '2.0';
	protected $_slug = 'syncs3';
	protected $_path = 'syncs3-lite/syncs3-lite.php';
	protected $_full_path = __FILE__;
	protected $_title = 'SyncS3';
	protected $_short_title = 'SyncS3';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return SyncS3Addon
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new SyncS3Addon();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_entry_created', array( $this, 'process_entry' ), 10, 2 );
		add_action( 'gform_field_advanced_settings', array( $this, 'upload_field_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
		add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'meta_box' ), 10, 3 );
		add_action( 'gform_settings_syncs3', array( $this, 'upgrade_info' ), 5 );
	}

	/**
	 * Displays the license status on the SyncS3 plugin settings page.
	 *
	 * @return void
	 */
	public function upgrade_info() {
		syncs3_upgrade_box( 'plugin_settings' );
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'SyncS3 Settings', 'syncs3' ),
				'fields' => array(
					array(
						'name' => 'amazons3_access_key',
						'tooltip' => esc_html__( 'Your Amazon AWS Access Key.', 'syncs3' ),
						'label' => esc_html__( 'Access Key', 'syncs3' ),
						'type' => 'text',
						'class' => 'large'
					),
					array(
						'name' => 'amazons3_secret_key',
						'tooltip' => esc_html__( 'Your Amazon AWS Secret Key.', 'syncs3' ),
						'label' => esc_html__( 'Secret Key', 'syncs3' ),
						'type' => 'text',
						'class' => 'large'
					),
					array(
						'name' => 'amazons3_bucket_name',
						'tooltip' => esc_html__( 'Default bucket name. This can be overridden on a form and field level.', 'syncs3' ),
						'label' => esc_html__( 'Default Bucket', 'syncs3' ),
						'type' => 'text',
						'class' => 'medium'
					),
					array(
						'name' => 'amazons3_region',
						'tooltip' => esc_html__( 'Region for the bucket. This can be overridden on a form and field level.', 'syncs3' ),
						'label' => esc_html__( 'Region', 'syncs3' ),
						'type' => 'text',
						'class' => 'small'
					)
				)
			)
		);
	}

	/**
	 * Adds custom settings to the field's Advanced tab
	 *
	 * @param  int 	$position 	Position
	 * @param  int 	$form_id  	Form ID
	 *
	 * @return void
	 */
	public function upload_field_settings( $position, $form_id ) {
		
		// Put settings at the very end
		if ( $position == -1 ) {
			?>
			<style>
				.syncs3-field-settings {
					display: none;
				}
				.ginput_container_fileupload ~ .ui-tabs .syncs3-field-settings {
					display: block;
					background-color: #f5f5f5; 
					padding: 20px; 
					margin-top: 20px;
				}
			</style>
			<div class="syncs3-field-settings">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Amazon S3 Upload Settings', 'syncs3' ); ?></h3>
				<li class="amazons3_enable_setting field_setting">
					<label for="field_amazons3_enable" class="section_label">
						<input type="checkbox" id="field_amazons3_enable" onclick="SetFieldProperty('enableS3Field', this.checked);" />
						<?php esc_html_e( 'Enable Uploads to S3', 'syncs3' ); ?>
						<?php gform_tooltip( 'form_field_amazons3_enable' ) ?>
					</label>
				</li>
				<?php syncs3_upgrade_box( 'field_settings' ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Script that runs in the form editor.
	 * This is responsible for binding the fields to Gravity Forms's save process.
	 *
	 * @return void
	 */
	public function editor_script(){
		?>
		<script>
			// Add setting to fileupload field type
			fieldSettings.fileupload += ", .amazons3_enable_setting";
	
			// binding to the load field settings event to initialize the checkbox
			jQuery(document).on("gform_load_field_settings", function(event, field, form){
				jQuery("#field_amazons3_enable").attr("checked", field["enableS3Field"] == true);
			});
		</script>
		<?php
	}

	/**
	 * Render custom tooltips.
	 *
	 * @param array 	$tooltips 	Tooltips
	 * 
	 * @return array
	 */
	public function add_tooltips( $tooltips ) {
		$tooltips['form_field_amazons3_enable'] = __( "<h6>Enable</h6>This will enable sending file uploads to Amazon S3.", 'syncs3' );
		return $tooltips;
	}

	/**
	 * Send files to Amazon S3 when a form is submitted.
	 *
	 * @param array 	$entry 	The entry currently being processed.
	 * @param array 	$form 	The form currently being processed.
	 *
	 * @return void
	 */
	public function process_entry( $entry, $form ) {

		$form_meta = RGFormsModel::get_form_meta( $form['id'] );
		$fields = $form_meta['fields'];

		// Check all file upload fields
		foreach ( $fields as $field ) {

			// Only act on file upload fields enabled for S3 uploads
			if ( 'fileupload' !== $field->type || ! $field->enableS3Field ) {
				continue;
			}

			$uploaded = syncs3_send_entry_files_to_s3( $entry, $form['id'], $field->id, syncs3_get_aws_keys( $form, $field ), $field->amazonS3UnlinkField );
		}
	}

	/**
	 * Adds a meta box to the Entry
	 *
	 * @param  array 	$meta_boxes 	Meta boxes
	 * @param  array 	$entry      	Entry
	 * @param  array 	$form       	Form
	 *
	 * @return array
	 */
	public function meta_box( $meta_boxes, $entry, $form ) {
		
		$meta_boxes['s3_urls'] = array(
			'title'    => esc_html__( 'S3 URLs', 'syncs3' ),
			'callback' => array( $this, 'render_mb' ),
			'context'  => 'normal',
			'priority' => 'high',
			'callback_args' => array(
				'entry' => $entry,
				'form' => $form
			)
		);

		return $meta_boxes;
	}

	/**
	 * Renders the S3 URLs meta box
	 *
	 * @param  array 	$args 	Args
	 *
	 * @return void
	 */
	public function render_mb( $args ) {

		SyncS3::autoload();

		// Get S3 URLs
		$s3_urls = gform_get_meta( $args['entry']['id'], 's3_urls' );

		?>
		<?php if ( ! empty( $s3_urls ) ) : ?>
			<table cellspacing="0" class="widefat fixed entry-detail-view">
				<thead>
					<tr>
						<th colspan="2">S3 URLs</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $s3_urls as $field_id => $urls ) : ?>
						<?php if ( ! empty( $urls ) ) : ?>
							<?php $field = GFAPI::get_field( $args['form'], $field_id ); ?>
							<tr>
								<td colspan="2" class="entry-view-field-name"><strong><?php echo $field['label']; ?></td>
							</tr>
							<tr>
								<td colspan="2">
									<ul>
										<?php foreach ( $urls as $url ) : ?>
											<?php
												if ( is_array( $url ) && isset( $url['file_url'] ) ) {
													$s3 = new S3Client( array(
														'version' => 'latest',
														'region' => $url['region'],
														'credentials' => array(
															'key' => $url['access_key'],
															'secret' => $url['secret_key'],
														),
													) );
													$cmd = $s3->getCommand( 'GetObject', [
													    'Bucket' => $url['bucket'],
													    'Key' => $url['key']
													]);
													$request = $s3->createPresignedRequest( $cmd, '+20 minutes' );
													$display = $url['file_url'];
													$url = (string) $request->getUri();
												} else {
													$display = $url;
												}
											?>
											<li>
												<a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_url( $display ); ?></a>
											</li>
										<?php endforeach; ?>
									</ul>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif;
		syncs3_upgrade_box( 'entry_metabox' );
	}
}