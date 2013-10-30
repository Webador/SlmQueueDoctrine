<?php

namespace SlmQueueDoctrine\Exception;

use RuntimeException as BaseRuntimeException;

/**
 * JobNotFoundException
 */
class JobNotFoundException extends BaseRuntimeException implements ExceptionInterface
{
}
