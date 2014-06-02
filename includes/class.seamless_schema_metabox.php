<?php

/**
 * Metadata box for posts' Schema.org properties
 */
class SeamlessSchemaMetabox
{
	/**
	 * Initialize metabox in backoffice post editor
	 * Called by do_meta_boxes action
	 * @param string $page
	 * @param string $context
	 * @return void
	 */
	public static function init($page, $context)
	{
		// Only display on post pages
		if ($context != 'normal' || !in_array($page, array('attachment', 'page', 'post')))
			return;

		add_meta_box('schema-metadata-box', __('Schema.org metadata', 'seamless-schema'), array('SeamlessSchemaMetabox', 'displayPostMetadataEditor'), $page, $context, 'default');
	}

	/**
	 * Display the metadata editor for the current post
	 */
	public static function displayPostMetadataEditor()
	{
		global $post;

		// Load post schema
		$schemaMetadata = new SeamlessSchemaMetadata();
		$schemaMetadata->setPost($post);

		// Display editor
		self::displayMetadataEditor($schemaMetadata);
	}

	/**
	 * Display the metadata editor
	 * @var SeamlessSchemaMetadata $schemaMetadata
	 */
	public static function displayMetadataEditor(SeamlessSchemaMetadata $schemaMetadata)
	{
		// Use nonce for verification
		echo '<input type="hidden" name="wp_schema_meta_box_nonce" value="', wp_create_nonce( basename(__FILE__) ), '" />';

		// Metadata type
		echo '<p><strong><label for="metadata_type">' . __("Content type:", 'seamless-schema') . '</label></strong>&nbsp;';
		echo '<select name="schema-content-type" id="schema-content-type">';
		foreach(SeamlessSchema::getSchemaTypes() as $typeId => $type)
			echo '<option value="' . esc_attr($typeId) . '"' . (($schemaMetadata->type == $typeId)?' selected':'') . '>' . esc_html($type['label']) . '</type>';
		echo '</select></p>';

		echo '<table id="schema-meta" class="wp-list-table widefat">';
		echo '<thead><tr><th class="propertyName">' . __('Name', 'seamless-schema') . '</th><th colspan="2">' . __('Value', 'seamless-schema') . '</th></tr></thead>';
		echo '<tbody></tbody>';
		echo '</table>';

		echo '<p><select id="schema-properties" name="schema-add-property" />&nbsp;<input type="button" value="&nbsp;+&nbsp;" class="button" id="schema-add-property"></p>';

		// Create initial schema data array for the script
		$initialSchemaData = array();
		foreach($schemaMetadata->data as $name => $value)
			if (in_array($name, $schemaMetadata->definedProperties))
				$initialSchemaData[] = array($name, $value);

		?>
<script type="text/javascript">

// Schema.org content types
var schemaContentTypes = <?php echo json_encode(SeamlessSchema::getSchemaTypes()); ?>;

// Schema.org properties
var schemaProperties   = <?php echo json_encode(SeamlessSchema::getSchemaProperties()); ?>;

// Schema.org data types
var schemaDataTypes    = <?php echo json_encode(SeamlessSchema::getSchemaDataTypes()); ?>;

// Standard properties
var standardProperties   = <?php echo json_encode($schemaMetadata->standardProperties); ?>;

// Initial form data
var initialSchemaData  = <?php echo json_encode($initialSchemaData); ?>;

jQuery(function() {

	/**
	 * Set the schema.org content type
	 * @param string type
	 * @returns
	 */
	function schema_set_content_type(type)
	{
		var select = jQuery('#schema-properties');
		select.html('');

		// Switching content type: consider all properties inexistant
		jQuery('#schema-meta tbody tr').addClass('inexistantProperty');

		for (var i in schemaContentTypes[type].properties)
		{
			var option = jQuery('<option />');
			option.val(schemaContentTypes[type].properties[i]);
			option.html(schemaContentTypes[type].properties[i]);
			option.attr('title', schemaProperties[schemaContentTypes[type].properties[i]].commentText);
			option.attr('id', 'schema-property-option-' + schemaContentTypes[type].properties[i]);

			// Highlight property in list if already selected
			if (jQuery("#schema-data-" + schemaContentTypes[type].properties[i]).length > 0)
				option.addClass('added');

			// This property is now existant, remove the inexistantProperty class
			jQuery('#schema-data-' + schemaContentTypes[type].properties[i]).removeClass('inexistantProperty');

			select.append(option);
		}

		// Add a specific class to the properties that can be overriden
		for (var i in standardProperties)
			jQuery('#schema-property-option-' + standardProperties[i]).addClass('standard');
	}

	/**
	 * Add a schema.org metadata row
	 * @param string property
	 * @param string value
	 * @returns void
	 */
	function add_schema_metadata_row(property, value)
	{
		var elementName = "schema-data-" + property;

		// Element already exists!
		if (jQuery('#' + elementName).length > 0)
			return;

		// Determine data type and validation data type
		var dataType           = schemaProperties[property].ranges[0];
		var dataTypeValidation = schemaProperties[property].ranges[schemaProperties[property].ranges.length - 1];

		switch(dataType)
		{
			case 'Boolean':
				var formElement = '<select><option value="True">True</option></option value="False">False</option></select>';
				break;

			case 'Integer':
			case 'Float':
			case 'Number':
				var formElement = '<input type="text" size="10" />';
				break;

			case 'Date':
			case 'Time':
			case 'DateTime':
				var formElement = '<input type="text" size="20" />';
				break;

			case 'URL':
				var formElement = '<input type="text" style="width: 100%" />';
				break;

			default:
				var formElement = '<textarea rows="2" style="width: 100%" />';
		}


		formElement = jQuery(formElement);
		formElement.attr('name', elementName);
		formElement.val(value); // Values are HTML encoded so they are decoded to prevent double encoding
		formElement.addClass('validation-' + dataTypeValidation);

		var row = jQuery('<tr id="schema_data_' + property + '"><td class="propertyName"><strong>' + property + '</strong></td><td /></tr>');
		row.attr('id',   elementName);

		row.children('td').last().append(formElement);

		var strHelp = schemaDataTypes[dataType].label;

		if (schemaDataTypes[dataType].format != null)
			strHelp += ' (<i>' + schemaDataTypes[dataType].format + '</i>)';

		if (schemaProperties[property].comment != '')
			strHelp += ' &ndash; ' + schemaProperties[property].comment;

		row.children('td').last().append(strHelp);
		row.append('<td class="removeButton"><input class="button" type="button" value="X" id="remove-' + property + '" /></td>');

		jQuery('#schema-meta tbody').append(row);

		// Remove button
		jQuery('#' + elementName + ' input[class=button]').click(function () {

			var property = jQuery(this).parents('tr').attr('id').replace(/^schema-data-/, '');
			jQuery(this).parents('tr').remove();

			// Un-highlight selected property in list
			jQuery('#schema-properties option[value=' + property + ']').removeClass('added');
		});

		// Highlight selected property in list
		jQuery('#schema-properties option[value=' + property + ']').addClass('added');

		// Highlight property row if does not belong to the content type model
		if (jQuery('#schema-properties option[value=' + property + ']').length == 0)
			row.addClass('inexistantProperty');
	}

	/**
	 * Validate the schema.org metadata format
	 * @returns boolean
	 */
	function validate_schema_metadata()
	{
		var errors = 0;

		for(var dataType in schemaDataTypes)
		{
			if (schemaDataTypes[dataType].regexp == null)
				continue;

			jQuery('.validation-' + dataType).each(function () {
				var reg = new RegExp(schemaDataTypes[dataType].regexp, "g");
				var element = jQuery(this);

				if (!/^\s+$/.test(element.val()) && !reg.test(element.val()))
				{
					element.addClass('invalidInput');
					errors++;
				}
				else
					element.removeClass('invalidInput');
			});
		}

		return errors == 0;
	}

	// Validation on submit
	jQuery('#post').on('submit', function(e){
		if (!validate_schema_metadata())
		{
			e.preventDefault();
			jQuery('#timestampdiv').show();
			jQuery('#publishing-action .spinner').hide();
			jQuery('#publish').prop('disabled', false).removeClass('button-primary-disabled');
			return false;
		}
	});

	// Initialize form
	schema_set_content_type('<?php echo $schemaMetadata->type ?>');

	for (var i in initialSchemaData)
		add_schema_metadata_row(initialSchemaData[i][0], initialSchemaData[i][1]);

	jQuery('#schema-content-type').change(function() {
		schema_set_content_type(jQuery(this).val());
	});

	jQuery('#schema-add-property').click(function() {
		add_schema_metadata_row(jQuery('#schema-properties').val(), '');
	});
});

</script>
		<?php
	}

	/**
	 * Save the post
	 * Called by save_post action
	 * @param int $post_id
	 */
	public function savePost($post_id)
	{
		// Verify nonce
		if (!isset($_POST['wp_schema_meta_box_nonce']) || !wp_verify_nonce($_POST['wp_schema_meta_box_nonce'], basename(__FILE__)))
			return $post_id;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post_id;

		// Check permissions
		if ('page' == $_POST['post_type'])
		{
			if (!current_user_can( 'edit_page', $post_id))
				return $post_id;
		}
		elseif (!current_user_can('edit_post', $post_id))
			return $post_id;

		// Get previous metadata
		$previousMetadata = get_metadata('post', $post_id);

		// Save content type
		$type = trim(wp_unslash($_POST['schema-content-type']));
		update_post_meta($post_id, '_schematype', $type);

		// Save new metadata
		foreach($_POST as $name => $value)
		{
			if (preg_match('/^schema-data-(.+)$/', $name, $matches))
			{
				$value = trim($value);
				$metadataName = '_schemadata_' . $matches[1];

				if (!empty($value))
				{
					// New value
					update_post_meta($post_id, $metadataName, $value);
					unset($previousMetadata[$metadataName]);
				}
			}
		}

		// Delete previous metadata
		foreach($previousMetadata as $dataName => $dataValue)
			if (preg_match('/^_schemadata_(.+)$/', $dataName))
				delete_post_meta($post_id, $dataName);
	}
}

// Back office hooks
add_action('do_meta_boxes', array('SeamlessSchemaMetabox', 'init'), 10, 2);
add_action('edit_post',     array('SeamlessSchemaMetabox', 'savePost'));