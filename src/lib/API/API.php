<?php
//    Pasteque API
//
//    Copyright (C) 2012-2017 Pasteque contributors
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\API;

/** Interface for all Pastèque APIs. */
interface API
{
    /**
     * Create the API from the application context.
     * @param \Pasteque\Server\AppContext $app The AppContext to read the
     * parameters of the constructor from.
     * @return \Pasteque\Server\API The created API.
     */
    public static function fromApp($app);

}
