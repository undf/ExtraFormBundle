<?php

namespace Undf\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer;

/**
 * Transforms between a normalized time and a localized time string/array.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
class DateTimeToStringsTransformer extends BaseDateTimeTransformer
{

    /**
     * Format used for generating strings
     * @var string
     */
    private $generateFormat;

    /**
     * Format used for parsing strings
     *
     * Different than the {@link $generateFormat} because formats for parsing
     * support additional characters in PHP that are not supported for
     * generating strings.
     *
     * @var string
     */
    private $parseFormat;

    /**
     * Whether to parse by appending a pipe "|" to the parse format.
     *
     * This only works as of PHP 5.3.7.
     *
     * @var Boolean
     */
    private $parseUsingPipe;

    /**
     * @var string
     */
    private $dateFormat;

    /**
     * @var string
     */
    private $timeFormat;

    /**
     * Transforms a \DateTime instance to a string
     *
     * @see \DateTime::format() for supported formats
     *
     * @param string  $inputTimezone  The name of the input timezone
     * @param string  $outputTimezone The name of the output timezone
     * @param string  $dateFormat         The date format
     * @param string  $timeFormat         The time format
     * @param Boolean $parseUsingPipe Whether to parse by appending a pipe "|" to the parse format
     *
     * @throws UnexpectedTypeException if a timezone is not a string
     */
    public function __construct($inputTimezone = null, $outputTimezone = null, $dateFormat = 'Y-m-d', $timeFormat = 'H:i', $parseUsingPipe = null)
    {
        parent::__construct($inputTimezone, $outputTimezone);

        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->generateFormat = $this->parseFormat = $dateFormat.' '.$timeFormat;

        // The pipe in the parser pattern only works as of PHP 5.3.7
        // See http://bugs.php.net/54316
        $this->parseUsingPipe = null === $parseUsingPipe ? version_compare(phpversion(), '5.3.7', '>=') : $parseUsingPipe;

        // See http://php.net/manual/en/datetime.createfromformat.php
        // The character "|" in the format makes sure that the parts of a date
        // that are *not* specified in the format are reset to the corresponding
        // values from 1970-01-01 00:00:00 instead of the current time.
        // Without "|" and "Y-m-d", "2010-02-03" becomes "2010-02-03 12:32:47",
        // where the time corresponds to the current server time.
        // With "|" and "Y-m-d", "2010-02-03" becomes "2010-02-03 00:00:00",
        // which is at least deterministic and thus used here.
        if ($this->parseUsingPipe && false === strpos($this->parseFormat, '|')) {
            $this->parseFormat .= '|';
        }
    }

    /**
     * Transforms a normalized date into a localized date string/array.
     *
     * @param \DateTime $dateTime Normalized date.
     *
     * @return string|array Localized date string/array.
     *
     * @throws TransformationFailedException If the given value is not an instance
     *                                       of \DateTime or if the date could not
     *                                       be transformed.
     */
    public function transform($dateTime)
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        // convert time to UTC before passing it to the formatter
        $dateTime = clone $dateTime;
        if ('UTC' !== $this->inputTimezone) {
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
        }

        return array(
            'date' => $dateTime->format($this->dateFormat),
            'time' => $dateTime->format($this->timeFormat)
        );
    }

    /**
     * Transforms a localized date string/array into a normalized date.
     *
     * @param string|array $value Localized date string/array
     *
     * @return \DateTime Normalized date
     *
     * @throws TransformationFailedException if the given value is not a string,
     *                                       if the date could not be parsed or
     *                                       if the input timezone is not supported
     */
    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (!isset($value['date'])) {
            throw new TransformationFailedException('Key "date" missed in array.');
        }

        if (!isset($value['time'])) {
            throw new TransformationFailedException('Key "time" missed in array.');
        }

        $value = $value['date'].' '.$value['time'];

        try {
            $outputTz = new \DateTimeZone($this->outputTimezone);
            $dateTime = \DateTime::createFromFormat($this->parseFormat, $value, $outputTz);
            $lastErrors = \DateTime::getLastErrors();
            if (0 < $lastErrors['warning_count'] || 0 < $lastErrors['error_count']) {
                throw new TransformationFailedException(
                implode(', ', array_merge(
                                array_values($lastErrors['warnings']), array_values($lastErrors['errors'])
                ))
                );
            }

            // On PHP versions < 5.3.7 we need to emulate the pipe operator
            // and reset parts not given in the format to their equivalent
            // of the UNIX base timestamp.
            if (!$this->parseUsingPipe) {
                list($year, $month, $day, $hour, $minute, $second) = explode('-', $dateTime->format('Y-m-d-H-i-s'));

                // Check which of the date parts are present in the pattern
                preg_match(
                        '/(' .
                        '(?P<day>[djDl])|' .
                        '(?P<month>[FMmn])|' .
                        '(?P<year>[Yy])|' .
                        '(?P<hour>[ghGH])|' .
                        '(?P<minute>i)|' .
                        '(?P<second>s)|' .
                        '(?P<dayofyear>z)|' .
                        '(?P<timestamp>U)|' .
                        '[^djDlFMmnYyghGHiszU]' .
                        ')*/', $this->parseFormat, $matches
                );

                // preg_match() does not guarantee to set all indices, so
                // set them unless given
                $matches = array_merge(array(
                    'day' => false,
                    'month' => false,
                    'year' => false,
                    'hour' => false,
                    'minute' => false,
                    'second' => false,
                    'dayofyear' => false,
                    'timestamp' => false,
                        ), $matches);

                // Reset all parts that don't exist in the format to the
                // corresponding part of the UNIX base timestamp
                if (!$matches['timestamp']) {
                    if (!$matches['dayofyear']) {
                        if (!$matches['day']) {
                            $day = 1;
                        }
                        if (!$matches['month']) {
                            $month = 1;
                        }
                    }
                    if (!$matches['year']) {
                        $year = 1970;
                    }
                    if (!$matches['hour']) {
                        $hour = 0;
                    }
                    if (!$matches['minute']) {
                        $minute = 0;
                    }
                    if (!$matches['second']) {
                        $second = 0;
                    }
                    $dateTime->setDate($year, $month, $day);
                    $dateTime->setTime($hour, $minute, $second);
                }
            }

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime->setTimeZone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (TransformationFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }

}
