<?php

namespace Terramar\Bundle\ResqueBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terramar\Bundle\ResqueBundle\DependencyInjection\TerramarResqueExtension;

class TerramarResqueBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new TerramarResqueExtension();
        }

        return $this->extension;
    }
}
