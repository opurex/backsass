<?php
//    Pastèque API
//
//    Copyright (C) 
//			2012 Scil (http://scil.coop)
//			2017 Karamel, Association Pastèque (karamel@creativekara.fr, https://pasteque.org)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server\System;

/** Date conversion utilities. */
class DateUtils
{
    /**
     * Hardcoded filter to reject dates after year 10000 because Doctrine
     * cannot parse them from database and crashes, but can still write them
     * without any issue.
     */
    private static function rejectLongDate($date) {
        if ($date === false) {
            return false;
        }
        if ($date->getTimestamp() >= 253402297200) { // 10000-01-01 00:00:00
            return false;
        }
        return $date;
    }

    /**
     * Check if two dates are equals in seconds.
     * @param mixed $a Something representing a date (passed to readDate).
     * @param mixed $b Something represinting a date (passed to readDate).
     * @return True if the dates are equals. False if one is not a date
     * or if they are different. Null and null are equals.
     */
    public static function equals($a, $b) {
        $dateA = static::readDate($a);
        $dateB = static::readDate($b);
        if ($dateA === false || $dateB === false) {
            return false;
        }
        if ($dateA === null && $dateB === null) { return true; }
        if ($dateA === null || $dateB === null) { return false; }
        return $dateA->getTimestamp() == $dateB->getTimestamp();
    }

    /**
     * Convert a DateTime to timestamp in seconds.
     * @param \DateTime|null $date The date to convert.
     * @return int|null Null if $date is null or invalid,
     * timestamp if already a timestamp or a DateTime. */
    public static function toTimestamp($date) {
        if ($date === null) { return null; }
        if ($date instanceof \DateTime) {
            return $date->getTimestamp();
        }
        if (is_int($date)) { return $date; }
        return null;
    }

    /**
     * Convert a timestamp in second to a DateTime.
     * Use DateUtils::readDate instead not to bother with the input format.
     * @param int $timestamp The timestamp.
     * @return DateTime|bool DateTime on success, false if not a timestamp. */
    public static function readTimestamp($timestamp) {
        if (!is_int($timestamp)) {
            return false;
        }
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date = static::rejectLongDate($date);
        return $date;
    }

    /**
     * Convert a date string to a DateTime.
     * Use DateUtils::readDate instead not to bother with the input format.
     * @param string $string The date. Valid format are YYYY-MM-DD
     * and YYYY-MM-DD HH:mm.
     * @return DateTime|bool DateTime on success, false if not a valid
     * date string.
     */
    public static function readString($string) {
        $date = \DateTime::createFromFormat('!Y-m-d', $string);
        if ($date === false) {
            $date = \DateTime::createFromFormat('Y-m-d H:i', $string);
        }
        $date = static::rejectLongDate($date);
        return $date;
    }

    /**
     * Automatically detect date format and get a DateTime.
     * @param int|string|\DateTime $input Something representing a date.
     * @return \DateTime|bool The date as a \DateTime, false if invalid.
     */
    public static function readDate($input) {
        if ($input === null) { return null; }
        if (is_numeric($input)) {
            if (is_int($input)) {
                return static::readTimestamp($input);
            } else {
                return static::readTimestamp(intval($input));
            }
        }
        if ($input instanceof \DateTime) {
            return $input;
        }
        if (is_string($input)) {
            return static::readString($input);
        }
        return false;
    }
}
