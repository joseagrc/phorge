<?php

/**
 * Event listener which is registered automatically, without requiring
 * configuration.
 *
 * Normally, event listeners must be registered via applications. This is
 * appropriate for structured listeners in libraries, but it adds a lot of
 * overhead and is cumbersome for one-off listeners.
 *
 * All concrete subclasses of this class are automatically registered at
 * startup. This allows it to be used with custom one-offs that can be dropped
 * into `phorge/src/extensions/`.
 */
abstract class PhorgeAutoEventListener extends PhorgeEventListener {}
