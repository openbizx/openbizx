<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Openbizx\Object;

/**
 * Helper for Object class. 
 * This helper needed because need only public access of Object class.
 *
 * @author agus
 */
class ObjecttHelper
{
    
 	/**
	 * Returns the public member variables of an object.
	 * This method is provided such that we can get the public member variables of an object.
	 * It is different from "get_object_vars()" because the latter will return private
	 * and protected variables if it is called within the object itself.
     * 
	 * @param object $object the object to be handled
	 * @return array the public member variables of the object
	 */
	public static function getObjectVars($object)
	{
		return get_object_vars($object);
	}
     

}
