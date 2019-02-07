<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Docman\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

class ItemRepresentation
{
    const TYPE_FOLDER   = 'folder';
    const TYPE_FILE   = 'file';
    const TYPE_LINK   = 'link';
    const TYPE_EMBEDDED = 'embedded';
    const TYPE_WIKI   = 'wiki';
    const TYPE_EMPTY  = 'empty';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $title;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $owner;

    /**
     * @var string {@type string}
     */
    public $last_update_date;

    /**
     * @var bool {@type bool}
     */
    public $user_can_write;

    /**
     * @var string
     */
    public $type;

    /**
     * @var FilePropertiesRepresentation | null
     */
    public $file_properties;

    /**
     * @var EmbeddedFilePropertiesRepresentation
     */
    public $embedded_file_properties;

    /**
     * @var LinkPropertiesRepresentation | null
     */
    public $link_properties;

    /**
     * @var WikiPropertiesRepresentation | null
     */
    public $wiki_properties;

    /**
    * @var int {@type int}
    */
    public $parent_id;
    /**
     * @var bool {@type bool}
     */
    public $is_expanded;

    /**
     * @var ItemLockInfoRepresentation | null
     */
    public $lock_info;

    public function build(
        \Docman_Item $item,
        MinimalUserRepresentation $owner,
        $user_can_write,
        $type,
        bool $is_expanded,
        ItemLockInfoRepresentation $lock_info = null,
        FilePropertiesRepresentation $file_properties = null,
        EmbeddedFilePropertiesRepresentation $embedded_file_properties = null,
        LinkPropertiesRepresentation $link_properties = null,
        WikiPropertiesRepresentation $wiki_properties = null
    ) {
        $this->id                       = JsonCast::toInt($item->getId());
        $this->title                    = $item->getTitle();
        $this->owner                    = $owner;
        $this->last_update_date         = JsonCast::toDate($item->getUpdateDate());
        $this->user_can_write           = $user_can_write;
        $this->type                     = $type;
        $this->file_properties          = $file_properties;
        $this->embedded_file_properties = $embedded_file_properties;
        $this->link_properties          = $link_properties;
        $this->wiki_properties          = $wiki_properties;
        $this->is_expanded              = $is_expanded;
        $this->lock_info                = $lock_info;

        $parent_id = JsonCast::toInt($item->getParentId());

        $this->parent_id = ($parent_id) ? $parent_id : 0;
    }
}
