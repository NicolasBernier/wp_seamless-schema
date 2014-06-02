<?php

/**
 * Schema.org schema handler
 */
class SeamlessSchema
{
	/**
	 * Enabled Schema.org types
	 * @var array
	 */
	protected static $schemaTypes = null;

	/**
	 * Accepted Schema.org properties
	 * @var array
	 */
	protected static $schemaProperties = null;

	/**
	 * Accepted Schema.org data types
	 * @var array
	 */
	protected static $schemaDataTypes = null;

	/**
	 * Initialize schema.org structure from rdfs.org
	 * @return void
	 */
	protected static function init()
	{
		// Schema already initialized
		if (!empty(self::$schemaTypes))
			return;

		self::$schemaTypes      = array();
		self::$schemaProperties = array();

		// http://www.pelagodesign.com/blog/2009/05/20/iso-8601-date-validation-that-doesnt-suck/
		$iso8601regexp = '^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$';

		// Accepted content types
		// TODO: set as configuration option
		$confSchemaTypes = "Article, Blog, WebPage, Book, Event, LocalBusiness, Organization, Person, Product, Review, ImageObject, AudioObject, VideoObject";

		// Structure cached file
		$structureCacheFile = SEAMLESS_SCHEMA_BASE . 'cache/schema_structure_' . md5($confSchemaTypes) . '.php';

		// Data types, ordered from the more restrictive to the less restrictive
		self::$schemaDataTypes  = array(
			'Boolean' => array(
				'label'  => __("Boolean", 'seamless-schema'),
				'format' => "True / False",
				'regexp' => '^(True|False)$',
			),
			'Integer' => array(
				'label'  => __("Integer number", 'seamless-schema'),
				'format' => null,
				'regexp' => '^[-+]?[0-9]+$'
			),
			'Float' => array(
				'label'  => __("Floating point number", 'seamless-schema'),
				'format' => null,
				'regexp' => '^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$'
			),
			'Number' => array(
				'label'  => __("Number", 'seamless-schema'),
				'format' => null,
				'regexp' => '^[-+]?[0-9]+$'
			),
			'Date' => array(
				'label'  => __("Date", 'seamless-schema'),
				'format' => __("ISO 8601", 'seamless-schema'),
				'regexp' => $iso8601regexp,
			),
			'Time' => array(
				'label'  => __("Time", 'seamless-schema'),
				'format' => __("ISO 8601", 'seamless-schema'),
				'regexp' => $iso8601regexp,
			),
			'DateTime' => array(
				'label'  => __("Date with time", 'seamless-schema'),
				'format' => __("ISO 8601", 'seamless-schema'),
				'regexp' => $iso8601regexp,
			),
			'URL' => array(
				'label'  => __("URL", 'seamless-schema'),
				'format' => "http://www.website.com",
				'regexp' => '^https?:\/\/.+'
			),
			'Text' => array(
				'label'  => __("Text", 'seamless-schema'),
				'format' => null,
				'regexp' => null,
			),
		);

		$fullSchemaStructure = null;

		// Load schema from cache
		if (file_exists($structureCacheFile))
		{
			require($structureCacheFile);

			self::$schemaTypes      = $schemaTypes;
			self::$schemaProperties = $schemaProperties;
		}
		// Reload schema from schema.rdfs.org
		else
		{
			$structureJson = @file_get_contents('http://schema.rdfs.org/all.json');

			if (!empty($structureJson))
			{
				$fullSchemaStructure = @json_decode($structureJson, true);
				if (empty($fullSchemaStructure))
					return;
			}
			// Problem retrieving schema from schema.rdfs.org
			else
				return;

			// Add properties

			foreach($fullSchemaStructure['properties'] as $name => $specs)
			{
				// Only accept canonical ranges
				$acceptedRanges = array_values(array_intersect(array_keys(self::$schemaDataTypes), $specs['ranges']));

				if (empty($acceptedRanges))
					$acceptedRanges[] = 'Text';

				self::$schemaProperties[$name] = array(
					'comment'     => $specs['comment'],
					'commentText' => $specs['comment_plain'],
					'ranges'      => $acceptedRanges,
				);
			}

			// Add content types

			if (!empty($confSchemaTypes))
				$acceptedSchemaTypes = explode(',', preg_replace('/[^a-zA-Z]+/', ',', $confSchemaTypes));
			else
				$acceptedSchemaTypes = array_keys($fullSchemaStructure['types']); // Accept all types

			sort($acceptedSchemaTypes);

			self::$schemaTypes = array();
			foreach($acceptedSchemaTypes as $type)
			{
				// Unknown type
				if (empty($fullSchemaStructure['types'][$type]))
					continue;

				self::$schemaTypes[$type] = array(
					'label'      => $fullSchemaStructure['types'][$type]['label'],
					'properties' => array()
				);

				// Link properties
				foreach($fullSchemaStructure['types'][$type]['properties'] as $property)
					if (!empty(self::$schemaProperties[$property]))
						self::$schemaTypes[$type]['properties'][] = $property;

				sort(self::$schemaTypes[$type]['properties'], SORT_STRING);
			}

			// Export schema structure in cache file
			$code = '<?php' . "\n" .
			        '$schemaTypes=' . self::arrayExportMinimized(self::$schemaTypes) . ";\n" .
			        '$schemaProperties=' . self::arrayExportMinimized(self::$schemaProperties) . ";";

			file_put_contents($structureCacheFile, $code);
		}
	}

	/**
	 * Export minimized array
	 * @param array $array
	 * @return string
	 */
	private static function arrayExportMinimized($array)
	{
		$code = var_export($array, true);

		// Minimize code
		$code = str_replace(" => ", '=>', $code);
		$code = preg_replace("/^\s+/m", '', $code);
		$code = preg_replace("/^[0-9]+=>/m", '', $code);
		$code = str_replace("array (\n", 'array(', $code);
		$code = str_replace("\n", '', $code);

		return $code;
	}

	/**
	 * Return schema.org supported properties
	 * @return array
	 */
	public static function getSchemaProperties()
	{
		self::init();
		return self::$schemaProperties;
	}

	/**
	 * Return schema.org types
	 * @return array
	 */
	public static function getSchemaTypes()
	{
		self::init();
		return self::$schemaTypes;
	}

	/**
	 * Return schema.org supported data types
	 * @return array
	 */
	public static function getSchemaDataTypes()
	{
		self::init();
		return self::$schemaDataTypes;
	}

	/**
	 * Return the properties for the given type.
	 * @param string $type
	 * @return array
	 */
	public static function getTypeProperties($type)
	{
		self::init();

		if (empty(self::$schemaTypes[$type]))
			return array();


		return self::$schemaTypes[$type]['properties'];
	}
}