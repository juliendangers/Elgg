<?php
	/**
	 * Elgg entities.
	 * Functions to manage all elgg entities (sites, collections, objects and users).
	 * 
	 * @package Elgg
	 * @subpackage Core
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Marcus Povey <marcus@dushka.co.uk>
	 * @copyright Curverider Ltd 2008
	 * @link http://elgg.org/
	 */

	/**
	 * @class ElggEntity The elgg entity superclass
	 * This class holds methods for accessing the main entities table.
	 * @author Marcus Povey <marcus@dushka.co.uk>
	 */
	abstract class ElggEntity implements Exportable, Importable
	{
		/** 
		 * The main attributes of an entity.
		 * Blank entries for all database fields should be created by the constructor.
		 * Subclasses should add to this in their constructors.
		 * Any field not appearing in this will be viewed as a 
		 */
		protected $attributes;
				
		/**
		 * Initialise the attributes array. 
		 * This is vital to distinguish between metadata and base parameters.
		 * 
		 * Place your base parameters here.
		 */
		protected function initialise_attributes()
		{
			// Create attributes array if not already created
			if (!is_array($this->attributes)) $this->attributes = array();
			
			$this->attributes['guid'] = "";
			$this->attributes['type'] = "";
			$this->attributes['subtype'] = "";
			$this->attributes['owner_guid'] = 0;
			$this->attributes['access_id'] = 0;
			$this->attributes['time_created'] = "";
			$this->attributes['time_updated'] = "";
		}
				
		/**
		 * Return the value of a given key.
		 * If $name is a key field (as defined in $this->attributes) that value is returned, otherwise it will
		 * then look to see if the value is in this object's metadata.
		 * 
		 * Q: Why are we not using __get overload here?
		 * A: Because overload operators cause problems during subclassing, so we put the code here and
		 * create overloads in subclasses. 
		 * 
		 * @param string $name
		 * @return mixed Returns the value of a given value, or null.
		 */
		public function get($name)
		{
			// See if its in our base attribute
			if (isset($this->attributes[$name])) {
				return $this->attributes[$name];
			}
			
			// No, so see if its in the meta data for this entity
			$meta = $this->getMetaData($name);
			if ($meta)
				return $meta;
			
			// Can't find it, so return null
			return null;
		}

		/**
		 * Set the value of a given key, replacing it if necessary.
		 * If $name is a base attribute (as defined in $this->attributes) that value is set, otherwise it will
		 * set the appropriate item of metadata.
		 * 
		 * Note: It is important that your class populates $this->attributes with keys for all base attributes, anything
		 * not in their gets set as METADATA.
		 * 
		 * Q: Why are we not using __set overload here?
		 * A: Because overload operators cause problems during subclassing, so we put the code here and
		 * create overloads in subclasses.
		 * 
		 * @param string $name
		 * @param mixed $value  
		 */
		public function set($name, $value)
		{
			if (array_key_exists($name, $this->attributes))
			{
				// Check that we're not trying to change the guid! 
				if ((array_key_exists('guid', $this->attributes)) && ($name=='guid'))
					return false;
					
				$this->attributes[$name] = $value;
			}
			else
				return $this->setMetaData($name, $value);
			
			return true;
		}
			
		/**
		 * Get a given piece of metadata.
		 * 
		 * @param string $name
		 */
		public function getMetaData($name)
		{
			$md = get_metadata_byname($this->getGUID(), $name);
			
			if ($md)
				return $md->value;
				
			return null;
		}
		
		/**
		 * Set a piece of metadata.
		 * 
		 * @param string $name
		 * @param mixed $value
		 * @param string $value_type
		 * @return bool
		 */
		public function setMetaData($name, $value, $value_type = "")
		{
			if (is_array($value))
			{
				foreach ($value as $v)
					if (!create_metadata($this->getGUID(), $name, $v, $value_type, $this->getOwner(), true)) return false;
					
				return true;
			}
			else
				return create_metadata($this->getGUID(), $name, $value, $value_type, $this->getOwner());
		}
		
		/**
		 * Clear metadata.
		 */
		public function clearMetaData()
		{
			return clear_metadata($this->getGUID());
		}
		
		/**
		 * Adds an annotation to an entity. By default, the type is detected automatically; however, 
		 * it can also be set. Note that by default, annotations are private.
		 * 
		 * @param string $name
		 * @param mixed $value
		 * @param int $access_id
		 * @param int $owner_id
		 * @param string $vartype
		 */
		function annotate($name, $value, $access_id = 0, $owner_id = 0, $vartype = "") 
		{ 
			return create_annotation($this->getGUID(), $name, $value, $vartype, $owner_id, $access_id);
		}
		
		/**
		 * Get the annotations for an entity.
		 *
		 * @param string $name
		 * @param int $limit
		 * @param int $offset
		 */
		function getAnnotations($name, $limit = 50, $offset = 0) 
		{ 
			return get_annotations($this->getGUID(), "", "", $name, "", 0, $limit, $offset);
		}
		
		/**
		 * Remove all annotations or all annotations for this entity.
		 *
		 * @param string $name
		 */
		function clearAnnotations($name = "")
		{
			return clear_annotations($this->getGUID(), $name);
		}
		
		/**
		 * Return the annotations for the entity.
		 *
		 * @param string $name The type of annotation.
		 */
		function countAnnotations($name) 
		{ 
			return count_annotations($this->getGUID(), "","",$name);
		}

		/**
		 * Get the average of an integer type annotation.
		 *
		 * @param string $name
		 */
		function getAnnotationsAvg($name) 
		{
			return get_annotations_avg($this->getGUID(), "","",$name);
		}
		
		/**
		 * Get the sum of integer type annotations of a given name.
		 *
		 * @param string $name
		 */
		function getAnnotationsSum($name) 
		{
			return get_annotations_sum($this->getGUID(), "","",$name);
		}
		
		/**
		 * Get the minimum of integer type annotations of given name.
		 *
		 * @param string $name
		 */
		function getAnnotationsMin($name)
		{
			return get_annotations_min($this->getGUID(), "","",$name);
		}
		
		/**
		 * Get the maximum of integer type annotations of a given name.
		 *
		 * @param string $name
		 */
		function getAnnotationsMax($name)
		{
			return get_annotations_max($this->getGUID(), "","",$name);
		}
		
		public function getGUID() { return $this->get('guid'); }
		public function getOwner() { return $this->get('owner_guid'); }
		public function getType() { return $this->get('type'); }
		public function getSubtype() { return get_subtype_from_id($this->get('owner_guid')); }
		public function getTimeCreated() { return $this->get('time_created'); }
		public function getTimeUpdated() { return $this->get('time_updated'); }
		
		/**
		 * Save generic attributes to the entities table.
		 */
		public function save()
		{
			if ($this->get('guid') > 0)
				return update_entity(
					$this->get('guid'),
					$this->get('owner_guid'),
					$this->get('access_id')
				);
			else
			{ 
				$this->attributes['guid'] = create_entity($this->attributes['type'], $this->attributes['subtype'], $this->attributes['owner_guid'], $this->attributes['access_id']); // Create a new entity (nb: using attribute array directly 'cos set function does something special!)
				if (!$this->attributes['guid']) throw new IOException("Unable to save new object's base entity information!"); 
				
				return $this->attributes['guid'];
			}
		}
		
		/**
		 * Load the basic entity information and populate base attributes array.
		 * 
		 * @param int $guid 
		 */
		protected function load($guid)
		{		
			$row = get_entity_as_row($guid);
			
			if ($row)
			{
				// Create the array if necessary - all subclasses should test before creating
				if (!is_array($this->attributes)) $this->attributes = array();
				
				// Now put these into the attributes array as core values
				$objarray = (array) $row;
				foreach($objarray as $key => $value) 
					$this->attributes[$key] = $value;
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Delete this entity.
		 */
		public function delete() 
		{ 
			return delete_entity($this->get('guid'));
		}

		/**
		 * Export this class into a stdClass containing all necessary fields.
		 * Override if you wish to return more information than can be found in $this->attributes (shouldn't happen) 
		 * 
		 * @return stdClass
		 */
		public function export() 
		{ 
			$tmp = new stdClass; 
			$tmp->attributes = $this->attributes; 
			$tmp->attributes['uuid'] = guid_to_uuid($this->getGUID());
			$tmp->attributes['owner_uuid'] = guid_to_uuid($this->owner_guid);
			return $tmp; 
		}
		
		/**
		 * Import data from an parsed xml data array.
		 * 
		 * @param array $data
		 * @param int $version 
		 */
		public function import(array $data, $version = 1)
		{
			if ($version == 1)
			{
				$uuid = "";
				
				// Get attributes
				foreach ($data['elements'][0]['elements'] as $attr)
				{
					$name = strtolower($attr['name']);
					$text = $attr['text'];
					
					switch ($name)
					{
						case 'uuid' : $uuid = $text; break;
						default : $this->attributes[$name] = $text;
					}
				}

				// Check uuid as local domain
				if (is_uuid_this_domain($uuid))
					throw new ImportException("$uuid belongs to this domain!");

				// See if this entity has already been imported, if so we don't need to create a new element
				$entity = get_entity_from_uuid($uuid);
				if ($entity)
					$this->attributes['guid'] = $entity->guid;
			
				// save
				if (!$this->save());
					throw new ImportException("There was a problem saving $uuid");
						
				// Tag this GUID with the UUID if this is a new entity
				if (!$entity)
					add_uuid_to_guid($this->attributes['guid'], $uuid);

				// return result
				return $this;
			}
			else
				throw new ImportException("Unsupported version ($version) passed to ElggEntity::import()");
		}
	}

	/**
	 * Return the integer ID for a given subtype, or false.
	 * 
	 * TODO: Move to a nicer place?
	 * 
	 * @param string $type
	 * @param string $subtype
	 */
	function get_subtype_id($type, $subtype)
	{
		global $CONFIG;
		
		$type = sanitise_string($type);
		$subtype = sanitise_string($subtype);
		
		$result = get_data_row("SELECT * from {$CONFIG->dbprefix}entity_subtypes where type='$type' and subtype='$subtype'");
		if ($result)
			return $result->id;
		
		return 0;
	}
	
	/**
	 * For a given subtype ID, return its identifier text.
	 *  
	 * TODO: Move to a nicer place?
	 * 
	 * @param string $subtype_id
	 */
	function get_subtype_from_id($subtype_id)
	{
		global $CONFIG;
		
		$subtype_id = (int)$subtype_id;
		
		$result = get_data_row("SELECT * from {$CONFIG->dbprefix}entity_subtypes where id=$subtype_id");
		if ($result)
			return $result->subtype;
		
		return false;
	}
	
	/**
	 * This function tests to see if a subtype has a registered class handler.
	 * 
	 * @param string $type The type
	 * @param string $subtype The subtype
	 * @return a class name or null
	 */
	function get_subtype_class($type, $subtype)
	{
		global $CONFIG;
		
		$type = sanitise_string($type);
		$subtype = sanitise_string($subtype);
		
		$result = get_data_row("SELECT * from {$CONFIG->dbprefix}entity_subtypes where type='$type' and subtype='$subtype'");
		if ($result)
			return $result->class;
		
		return NULL;
	}
	
	/**
	 * This function will register a new subtype, returning its ID as required.
	 * 
	 * @param string $type The type you're subtyping
	 * @param string $subtype The subtype label
	 * @param string $class Optional class handler (if you don't want it handled by the generic elgg handler for the type)
	 */
	function add_subtype($type, $subtype, $class = "")
	{
		global $CONFIG;
		
		$type = sanitise_string($type);
		$subtype = sanitise_string($subtype);
		$class = sanitise_string($class);
		
		$id = get_subtype_id($type, $subtype);
		
		if (!$id)
			return insert_data("insert into {$CONFIG->dbprefix}entity_subtypes (type, subtype, class) values ('$type','$subtype','$class')");
		
		return $id;
	}
	
	/**
	 * Update an existing entity.
	 *
	 * @param int $guid
	 * @param int $owner_guid
	 * @param int $access_id
	 */
	function update_entity($guid, $owner_guid,  $access_id)
	{
		global $CONFIG;
		
		$guid = (int)$guid;
		$owner_guid = (int)$owner_guid;
		$access_id = (int)$access_id;
		$time = time();
		
		$access = get_access_list();
		
		
		return update_data("UPDATE {$CONFIG->dbprefix}entities set owner_guid='$owner_guid', access_id='$access_id', time_updated='$time' WHERE guid=$guid and (access_id in {$access} or (access_id = 0 and owner_guid = {$_SESSION['id']}))");
	}
	
	/**
	 * Create a new entity of a given type.
	 * 
	 * @param string $type
	 * @param string $subtype
	 * @param int $owner_guid
	 * @param int $access_id
	 * @return mixed The new entity's GUID or false.
	 */
	function create_entity($type, $subtype, $owner_guid, $access_id)
	{
		global $CONFIG;
	
		$type = sanitise_string($type);
		$subtype = add_subtype($subtype);
		$owner_guid = (int)$owner_guid; 
		$access_id = (int)$access_id;
		$time = time();
					
		if ($type=="") throw new InvalidParameterException("Entity type must be set.");
		if ($owner_guid==0) throw new InvalidParameterException("owner_guid must not be 0");
		
		return insert_data("INSERT into {$CONFIG->dbprefix}entities (type, subtype, owner_guid, access_id, time_created, time_updated) values ('$type',$subtype, $owner_guid, $access_id, $time, $time)");
	}
	
	/**
	 * Retrieve the entity details for a specific GUID, returning it as a stdClass db row.
	 * 
	 * You will only get an object if a) it exists, b) you have access to it.
	 *
	 * @param int $guid
	 */
	function get_entity_as_row($guid)
	{
		global $CONFIG;
		
		$guid = (int)$guid;
		
		$access = get_access_list();
		
		return get_data_row("SELECT * from {$CONFIG->dbprefix}entities where guid=$guid and (access_id in {$access} or (access_id = 0 and owner_guid = {$_SESSION['id']}))");
	}
	
	/**
	 * Create an Elgg* object from a given entity row. 
	 */
	function entity_row_to_elggstar($row)
	{
		if (!($row instanceof stdClass))
			return $row;
			
		// See if there are any registered subtype handler classes for this type and subtype
		$classname = get_subtype_class($row->type, $row->subtype);
		if ($classname!="")
		{
			$tmp = $classname($row);
			
			if (!($tmp instanceof ElggEntity))
				throw new ClassException("$classname is not an ElggEntity.");
				
		}
		else
		{
			switch ($row->type)
			{
				case 'object' : 
					return new ElggObject($row);
				case 'user' : 
					return new ElggUser($row);
				case 'collection' : 
					return new ElggCollection($row); 
				case 'site' : 
					return new ElggSite($row); 
				default: default : throw new InstallationException("Type {$row->type} is not supported. This indicates an error in your installation, most likely caused by an incomplete upgrade.");
			}
		}
		
		return false;
	}
	
	/**
	 * Return the entity for a given guid as the correct object.
	 * @param $guid
	 * @return a child of ElggEntity appropriate for the type.
	 */
	function get_entity($guid)
	{
		return entity_row_to_elggstar(get_entity_as_row($guid));
	}
	
	/**
	 * Return entities matching a given query.
	 * 
	 * @param string $type
	 * @param string $subtype
	 * @param int $owner_guid
	 * @param string $order_by
	 * @param int $limit
	 * @param int $offset
	 */
	function get_entities($type = "", $subtype = "", $owner_guid = 0, $order_by = "time_created desc", $limit = 10, $offset = 0)
	{
		global $CONFIG;
		
		$type = sanitise_string($type);
		$subtype = get_subtype_id($type, $subtype);
		$owner_guid = (int)$owner_guid;
		$order_by = sanitise_string($order_by);
		$limit = (int)$limit;
		$offset = (int)$offset;
		
		$access = get_access_list();
		
		$where = array();
		
		if ($type != "")
			$where[] = "type='$type'";
		if ($subtype)
			$where[] = "subtype=$subtype";
		if ($owner_guid != "")
			$where[] = "owner_guid='$owner_guid'";
		
		$query = "SELECT * from {$CONFIG->dbprefix}entities where ";
		foreach ($where as $w)
			$query .= " $w and ";
		$query .= " (access_id in {$access} or (access_id = 0 and owner_guid = {$_SESSION['id']}))"; // Add access controls
		$query .= " order by $order_by limit $limit, $offset"; // Add order and limit
		
		return get_data($query, "entity_row_to_elggstar");
	}
	
	/**
	 * Return entities matching a given query joining against a relationship.
	 * 
	 * @param string $relationship The relationship eg "friends_of"
	 * @param int $relationship_guid The guid of the entity to use query
	 * @param bool $inverse_relationship Reverse the normal function of the query to instead say "give me all entities for whome $relationship_guid is a $relationship of"
	 * @param string $type 
	 * @param string $subtype
	 * @param int $owner_guid
	 * @param string $order_by
	 * @param int $limit
	 * @param int $offset
	 */
	function get_entities_from_relationship($relationship, $relationship_guid, $inverse_relationship = false, $type = "", $subtype = "", $owner_guid = 0, $order_by = "time_created desc", $limit = 10, $offset = 0)
	{
		global $CONFIG;
		
		$relationship = sanitise_string($relationship);
		$relationship_guid = (int)$relationship_guid;
		$inverse_relationship = (bool)$inverse_relationship;
		$type = sanitise_string($type);
		$subtype = get_subtype_id($type, $subtype);
		$owner_guid = (int)$owner_guid;
		$order_by = sanitise_string($order_by);
		$limit = (int)$limit;
		$offset = (int)$offset;
		
		$access = get_access_list();
		
		$where = array();
		
		if ($relationship!="")
			$where[] = "r.relationship='$relationship'";
		if ($relationship_guid)
			$where[] = ($inverse_relationship ? "r.guid_one='$relationship'" : "r.guid_two='$relationship'");
		if ($type != "")
			$where[] = "e.type='$type'";
		if ($subtype)
			$where[] = "e.subtype=$subtype";
		if ($owner_guid != "")
			$where[] = "e.owner_guid='$owner_guid'";
		
		// Select what we're joining based on the options
		$joinon = "r.guid_two=e.guid";
		if (!$inverse_relationship)
			$joinon = "r.guid_one=e.guid";	
			
		$query = "SELECT * from {$CONFIG->dbprefix}entities e JOIN {$CONFIG->dbprefix}entity_relationships r on $joinon where ";
		foreach ($where as $w)
			$query .= " $w and ";
		$query .= " (e.access_id in {$access} or (e.access_id = 0 and e.owner_guid = {$_SESSION['id']}))"; // Add access controls
		$query .= " order by $order_by limit $limit, $offset"; // Add order and limit
		
		return get_data($query, "entity_row_to_elggstar");
	}
	
	/**
	 * Delete a given entity.
	 * 
	 * @param int $guid
	 */
	function delete_entity($guid)
	{
		global $CONFIG;
		
		// TODO Make sure this deletes all metadata/annotations/relationships/etc!!
		
		$guid = (int)$guid;
		
		$access = get_access_list();
		
		return delete_data("DELETE from {$CONFIG->dbprefix}entities where where guid=$guid and (access_id in {$access} or (access_id = 0 and owner_guid = {$_SESSION['id']}))"); 
		
	}

	/**
	 * Define an arbitrary relationship between two entities.
	 * This relationship could be a friendship, a group membership or a site membership.
	 * 
	 * This function lets you make the statement "$guid_one has $relationship with $guid_two".
	 * 
	 * TODO: Access controls? Are they necessary - I don't think so since we are defining 
	 * relationships between and anyone can do that. The objects should patch some access 
	 * controls over the top tho.
	 * 
	 * @param int $guid_one
	 * @param string $relationship 
	 * @param int $guid_two
	 */
	function add_entity_relationship($guid_one, $relationship, $guid_two)
	{
		global $CONFIG;
		
		$guid_one = (int)$guid_one;
		$relationship = sanitise_string($relationship);
		$guid_two = (int)$guid_two;
			
		return insert_data("INSERT into {$CONFIG->dbprefix}entity_relationships (guid_one, relationship, guid_two) values ($guid_one, 'relationship', $guid_two)");
	}
	
	/**
	 * Determine whether or not a relationship between two entities exists
	 *
	 * @param int $guid_one The GUID of the entity "owning" the relationship
	 * @param string $relationship The type of relationship
	 * @param int $guid_two The GUID of the entity the relationship is with
	 * @return true|false
	 */
	function check_entity_relationship($guid_one, $relationship, $guid_two)
	{
		global $CONFIG;
		
		$guid_one = (int)$guid_one;
		$relationship = sanitise_string($relationship);
		$guid_two = (int)$guid_two;
			
		if ($row = get_data_row("select guid_one from {$CONFIG->dbprefix}entity_relationships where guid_one=$guid_one and relationship='$relationship' and guid_two=$guid_two limit 1")) {
			return true;
		}
		return false;
	}

	/**
	 * Remove an arbitrary relationship between two entities.
	 * 
	 * @param int $guid_one
	 * @param string $relationship 
	 * @param int $guid_two
	 */
	function remove_entity_relationship($guid_one, $relationship, $guid_two)
	{
		global $CONFIG;
		
		$guid_one = (int)$guid_one;
		$relationship = sanitise_string($relationship);
		$guid_two = (int)$guid_two;
			
		return delete_data("DELETE from {$CONFIG->dbprefix}entity_relationships where guid_one=$guid_one and relationship='$relationship' and guid_two=$guid_two");
	}

	/**
	 * Handler called by trigger_plugin_hook on the "export" event.
	 */
	function export_entity_plugin_hook($hook, $entity_type, $returnvalue, $params)
	{
		// Sanity check values
		if ((!is_array($params)) && (!isset($params['guid'])))
			throw new InvalidParameterException("GUID has not been specified during export, this should never happen.");
			
		if (!is_array($returnvalue))
			throw new InvalidParameterException("Entity serialisation function passed a non-array returnvalue parameter");
			
		$guid = (int)$params['guid'];
		
		// Get the entity
		$entity = get_entity($guid);
		
		if ($entity instanceof ElggEntity)
			$returnvalue[] = $entity; // Add object to list of things to serialise - actual serialisation done later

		return $returnvalue;
	}
	
	/**
	 * Import an entity.
	 * This function checks the passed XML doc (as array) to see if it is a user, if so it constructs a new 
	 * elgg user and returns "true" to inform the importer that it's been handled.
	 */
	function import_entity_plugin_hook($hook, $entity_type, $returnvalue, $params)
	{
		$name = $params['name'];
		$element = $params['element'];
		
		$tmp = NULL;
		
		switch ($name)
		{
			case 'ElggUser' : $tmp = new ElggUser(); break;
			case 'ElggSite' : $tmp = new ElggSite(); break;
			case 'ElggConnection' : $tmp = new ElggConnection(); break;
			case 'ElggObject' : $tmp = new ElggObject(); break;
		}
		
		if ($tmp)
		{
			$tmp->import($element);
			
			return $tmp;
		}
	}
	
	/** Register the import hook */
	register_plugin_hook("import", "all", "import_entity_plugin_hook", 0);
	
	/** Register the hook, ensuring entities are serialised first */
	register_plugin_hook("export", "all", "export_entity_plugin_hook", 0);
	
?>