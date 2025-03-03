<?php

namespace Symfony\Component\Messenger\Handler {
    use function interface_exists;

    // The interface has been removed on SF 7, so we need to create it for compatibility
    if (!interface_exists(MessageHandlerInterface::class)) {
        interface MessageHandlerInterface {}
    }
}
