<?php

/**
 * Schema.org metadata set
 */
class SeamlessSchemaMetadata
{
	/**
	 * Related post
	 * @var WP_Post
	 */
	protected $post = null;

	/**
	 * Content type
	 * @var string
	 */
	protected $type = null;

	/**
	 * Metadata
	 * @var array
	 */
	protected $data = array();

	/**
	 * Properties extracted from standard post data
	 * @var array
	 */
	protected $standardProperties = array();

	/**
	 * User-defined properties, including standard and custom ones
	 * @var array
	 */
	protected $definedProperties = array();

	/**
	 * Constructs a new SeamlessSchema
	 * @param string $type Content type
	 * @param array  $data Initial data
	 */
	public function __construct($type = 'Blog', $data = array())
	{
		$this->clear();
		$this->type = $type;
		$this->data = $data;
	}

	public function __get($attribute)
	{
		return $this->$attribute;
	}

	/**
	 * Set Wordpress post
	 * @param WP_Post $post
	 */
	public function setPost(WP_Post $post)
	{
		$this->post = $post;
		$this->initFromPost();
	}

	/**
	 * Clear data
	 */
	public function clear()
	{
		$this->type = null;
		$this->data = array();
		$this->standardProperties = array();
		$this->definedProperties = array();
	}

	/**
	 * Initialize data from post
	 */
	protected function initFromPost()
	{
		$this->clear();

		// Determine content type
		switch ($this->post->post_type)
		{
			case 'attachment':
				// Determine attachment type
				$mimeType = explode('/', strtolower(get_post_mime_type($this->post->ID)));
				$attachmentMetadata = wp_get_attachment_metadata($this->post->ID);

				switch ($mimeType[0])
				{
					case 'image':
						$this->type = 'ImageObject';

						if (!empty($attachmentMetadata['width']))
							$this->data['width'] = $attachmentMetadata['width'];

						if (!empty($attachmentMetadata['height']))
							$this->data['height'] = $attachmentMetadata['height'];

						$this->data['thumbnailUrl'] = $this->data['image'] = wp_get_attachment_url($this->post->ID);

						break;

					case 'audio':
						$this->type = 'AudioObject';

						break;

					case 'video':
						$this->type = 'VideoObject';

						if (!empty($attachmentMetadata['width']))
							$this->data['width'] = $attachmentMetadata['width'];

						if (!empty($attachmentMetadata['height']))
							$this->data['height'] = $attachmentMetadata['height'];

						break;

					default:
						$this->type = 'Thing';
				}
				break;

			case 'post':
			case 'page':

			default:
				$this->type = 'Article';
		}

		// Title
		$this->data['name'] = trim(wp_specialchars_decode($this->post->post_title, ENT_QUOTES));

		// Description
		$this->data['description'] = trim(wp_specialchars_decode($this->post->post_excerpt, ENT_QUOTES));

		// Modification date
		$this->data['dateModified'] = str_replace(' ', 'T', $this->post->post_modified_gmt) . 'Z';

		// URL
		$this->data['url'] = get_permalink($this->post->ID);

		// Author
		$author = get_userdata($this->post->post_author);
		if (!empty($author) && !empty($author->data->display_name))
			$this->data['author'] = $author->data->display_name;

		// Image
		$thumbnailId = get_post_thumbnail_id($this->post->ID);
		if (!empty($thumbnailId) && empty($this->data['image']))
		{
			// Call standard hooks
			$size = apply_filters('post_thumbnail_size', 'medium');

			do_action('begin_fetch_post_thumbnail_html', $this->post->ID, $thumbnailId, $size);
			if (in_the_loop())
				update_post_thumbnail_cache();

			$thumbnailSrc = wp_get_attachment_image_src($thumbnailId, $size);

			$this->data['thumbnailUrl'] = $this->data['image'] = $thumbnailSrc[0];
		}

		// At this point, we have the standard values
		$this->standardProperties = array_keys($this->data);

		// Get the user-defined type and metadata

		$userType = get_metadata('post', $this->post->ID, '_schematype', true);
		if (!empty($userType))
			$this->type = $userType;

		$metadata = get_metadata('post', $this->post->ID);
		foreach($metadata as $dataName => $dataValues)
			if (preg_match('/^_schemadata_(.+)$/', $dataName, $matches))
			{
				$this->definedProperties[] = $matches[1];
				$this->data[$matches[1]] = reset($dataValues);
			}
	}

	/**
	 * Load from settings
	 * @param string $section
	 * @param string $locale
	 */
	public function loadFromSettings($section, $locale = null)
	{
		$this->clear();

		if (empty($locale))
			$locale = get_locale();

		// Get default type
		$this->type = get_option('seamless_schema_type_' . $section . '_' . $locale, 'Blog');

		// Get the default standard values

		// Title
		$titleParts = explode('—', wp_title('—', false, 'right'));
		array_pop($titleParts);
		$this->data['name'] = trim(preg_replace('/\s+/', ' ', implode(' — ', $titleParts)));

		// Site description
		$this->data['description'] = trim(get_bloginfo('description'));

		// Site image
		$headerImageUrl = get_header_image();
		if (!empty($headerImageUrl))
			$this->data['image'] = $headerImageUrl;

		$this->standardProperties = array_keys($this->data);

		// Get values from configuration
		$data = get_option('seamless_schema_data_' . $section . '_' . $locale, array());
		if (!empty($data))
		{
			$this->data = array_merge($this->data, $data);
			$this->definedProperties = array_keys($data);
		}
	}

	/**
	 * Load from current context
	 * @param string $context Context name. Can be author, category or tag
	 * @param string $locale
	 */
	public function loadFromContext($context, $locale = null)
	{
		$this->loadFromSettings('default', $locale);

		switch($context)
		{
			case 'author':
				$this->type = 'Person';
				$this->data['name']        = get_the_author_meta('display_name');
				$this->data['description'] = strip_tags(get_the_author_meta('description'));
				$this->data['url']         = get_the_author_meta('user_url');
				// Better keep this private
				//$this->data['email']       = get_the_author_meta('user_email');
				$this->data['image']       = 'http://gravatar.com/avatar/' . md5(strtolower(trim(get_the_author_meta('user_email'))));
				break;

			case 'category':
				$category = reset(get_the_category());
				$this->type = 'CollectionPage';
				$this->data['name']        = $category->name;
				$categoryDescription = trim(strip_tags($category->description));
				if (!empty($categoryDescription))
					$this->data['description'] = $categoryDescription;
				break;

			case 'tag':
				$tagDescription = trim(strip_tags(tag_description()));
				if (!empty($tagDescription))
					$this->data['description'] = $tagDescription;
				$this->type = 'CollectionPage';
				break;

			case 'search':
				$this->type = 'SearchResultsPage';
				break;

			default:
		}
	}
}