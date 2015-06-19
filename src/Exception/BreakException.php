<?php

namespace Port\Steps\Exception;

use Port\Exception;

/**
 * Thrown when the processing of the pipeline should be stopped
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class BreakException extends \Exception implements Exception
{

}
