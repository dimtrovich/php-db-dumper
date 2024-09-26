<?php

namespace Dimtrovich\DbDumper;

final class Option
{
	// Same as mysqldump.
	const MAXLINESIZE = 1000000;

	const CHARSET_UTF8    = 'utf8';
	const CHARSET_UTF8MB4 = 'utf8mb4';
	const CHARSET_BINARY  = 'binary';

	const COMPRESSION_GZIP       = 'Gzip';
	const COMPRESSION_BZIP2      = 'Bzip2';
	const COMPRESSION_NONE       = 'None';
	const COMPRESSION_GZIPSTREAM = 'GzipStream';

	public string $compress              = self::COMPRESSION_NONE;
	public string $default_character_set = self::CHARSET_UTF8;
	public string $where                 = '';

    public int $net_buffer_length = self::MAXLINESIZE;

	public array $include_tables = [];
	public array $exclude_tables = [];
	public array $include_views  = [];
	public array $init_commands  = [];
	public array $no_data        = [];

	public bool $if_not_exists        = false;
	public bool $reset_auto_increment = false;
	public bool $add_drop_database    = false;
	public bool $add_drop_table       = false;
	public bool $add_drop_trigger     = true;
	public bool $add_locks            = true;
	public bool $complete_insert      = false;
	public bool $databases            = false;
	public bool $disable_keys         = true;
	public bool $extended_insert      = true;
	public bool $events               = false;
	public bool $hex_blob             = true;   /* faster than escaped onent */
	public bool $insert_ignore        = false;
	public bool $no_autocommit        = true;
	public bool $no_create_db         = false;
	public bool $no_create_info       = false;
	public bool $lock_tables          = true;
	public bool $routines             = false;
	public bool $single_transaction   = true;
	public bool $skip_triggers        = false;
	public bool $skip_tz_utc          = false;
	public bool $skip_comments        = false;
	public bool $skip_dump_date       = false;
	public bool $skip_definer         = false;

	/* deprecated */
    public bool $disable_foreign_keys_check = true;


	private array $options = [];

	public function __construct(array $options)
	{
		$this->setOptions($options);

		$this->init_commands[] = 'SET NAMES ' . $this->default_character_set;

        if (false === $this->skip_tz_utc) {
            $this->init_commands[] = "SET TIME_ZONE='+00:00'";
        }

        // If no include-views is passed in, dump the same views as tables, mimic mysqldump behaviour.
        if ($this->include_views === []) {
            $this->include_views = $this->include_tables;
        }
	}

	/**
	 * Defini les options d'exportation de la base de donnees
	 */
	public function setOptions(array $options = []): self
	{
		foreach ($options as $key => $val) {
			if (property_exists($this, $key)) {
				$this->$key = $val;
				unset($options[$key]);
			}
		}

		$this->options = $options;

		return $this;
	}

	public function __get($name)
	{
		return $this->options[$name] ?? null;
	}

	public function __set($name, $value)
	{
		$this->options[$name] = $value;
	}
}
