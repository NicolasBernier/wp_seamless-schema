<?php

class SeamlessSchemaSettingsPage
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'addPluginPage'));
	}

	/**
	 * Check Polylang support
	 * @return boolean
	 */
	public function hasPolylang()
	{
		return defined('POLYLANG_VERSION');
	}

	/**
	 * Return supported languages
	 * @return array
	 */
	public function getLanguages()
	{
		$languages = array();

		if ($this->hasPolylang())
		{
			global $polylang;
			foreach($polylang->model->get_languages_list() as $language)
				$languages[$language->locale] = array(
					'slug' => $language->slug,
					'name' => $language->name
				);
		}
		else
		{
			$locale = get_locale();
			$slug   = preg_replace('/_.+$/', '', $locale);

			$languages[$locale] = array(
				'slug' => $slug,
				'name' => $locale
			);
		}

		return $languages;
	}

	/**
	 * Add options page
	 */
	public function addPluginPage()
	{
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin',
			'Seamless Schema',
			'manage_options',
			'seamless-schema-admin',
			array($this, 'createAdminPage')
		);
	}

	/**
	 * Return the menu label for the section and the locale
	 * @param string $section
	 * @param string $locale
	 * @return string
	 */
	protected function getMenuLabel($section, $locale = null)
	{
		if ($locale && $this->hasPolylang())
		{
			$languages = $this->getLanguages();

			switch ($section)
			{
				case 'homepage':
					return sprintf(__('Homepage settings for %s', 'seamless-schema'), $languages[$locale]['name']);

				case 'default':
					return sprintf(__('Default settings settings for %s', 'seamless-schema'), $languages[$locale]['name']);

				default:
					return null;
			}
		}
		else
		{
			switch ($section)
			{
				case 'homepage':
					return __('Homepage settings', 'seamless-schema');

				case 'default':
					return __('Default settings', 'seamless-schema');

				default:
					return null;
			}
		}
	}

	/**
	 * Options page callback
	 */
	public function createAdminPage()
	{
		$sections = array('homepage', 'default');
		$pageUrl = 'options-general.php?page=seamless-schema-admin';

		switch(@$_GET['opt'])
		{
			case 'homepage':
			case 'default':
				$opt = $_GET['opt'];
				break;

			default:
				$opt = null;
		}

		if (empty($_GET['locale']))
			$locale = get_locale();
		else
			$locale = $_GET['locale'];

		if (!empty($_POST))
			$this->saveForm($opt, $locale);

		$schemaMetadata = new SeamlessSchemaMetadata();
		$schemaMetadata->loadFromSettings($opt, $locale);

		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Seamless Schema Settings', 'seamless-schema'); ?></h2>

		<?php
			if (!$opt)
			{
				echo esc_html(__('Settings list', 'seamless-schema'));

				// Generate menu
				if ($this->hasPolylang())
				{
					foreach($sections as $section)
					{
						echo '<h3>' . esc_html($this->getMenuLabel($section)) . '</h3><ul>';
						foreach($this->getLanguages() as $languageLocale => $language)
							echo '<li><a href="' . $pageUrl . '&opt=' . $section . '&locale=' . $languageLocale . '">' . $this->getMenuLabel($section, $languageLocale) . '</a></li>';
						echo '</ul>';
					}
				}
				else
				{
					echo '<ul>';
					foreach($sections as $section)
						echo '<li><a href="' . $pageUrl . '&opt=' . $section . '">' . $this->getMenuLabel($section) . '</a></li>';
					echo '</ul>';
				}
			}
			else
			{
				echo '<a href="' . $pageUrl . '">' . esc_html(__('Settings list', 'seamless-schema')) . '</a>';
				echo ' &gt; ' . $this->getMenuLabel($opt, $locale);
		?>
				<h3><?php echo(esc_html($this->getMenuLabel($opt, $locale))); ?></h3>
				<form method="post" id="post">
					<?php SeamlessSchemaMetabox::displayMetadataEditor($schemaMetadata); ?>
					<?php	submit_button(); ?>
				</form>
		<?php
			}
		?>

		</div>
		<?php
	}

	/**
	 * Save settings from form
	 * @param string $section
	 * @param string $locale
	 */
	public function saveForm($section, $locale)
	{
		// Get metadata
		$metadata = array();
		foreach($_POST as $name => $value)
			if (preg_match('/^schema-data-(.+)$/', $name, $matches))
				$metadata[$matches[1]] = trim(wp_unslash($value));

		// Save metadata and content type
		$type = trim(wp_unslash($_POST['schema-content-type']));
		update_option('seamless_schema_data_' . $section . '_' . $locale, $metadata);
		update_option('seamless_schema_type_' . $section . '_' . $locale, $type);
	}
}

if(is_admin())
	$my_seamless_schema_settings_page = new SeamlessSchemaSettingsPage();