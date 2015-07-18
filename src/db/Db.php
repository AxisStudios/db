<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
namespace soloproyectos\db;

/**
 * Helper Db class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
class Db
{
    /**
     * Quotes an identifier.
     * 
     * @param string $identifier Identifier
     * 
     * @return string
     */
    public static function quoteId($identifier)
    {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }
}
