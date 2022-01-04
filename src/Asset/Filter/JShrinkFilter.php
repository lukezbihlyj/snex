<?php

namespace Snex\Asset\Filter;

use Assetic\Filter\BaseFilter;
use Assetic\Contracts\Asset\AssetInterface;
use JShrink\Minifier;

class JShrinkFilter extends BaseFilter
{
    public function filterDump(AssetInterface $asset) : void
    {
        $asset->setContent(Minifier::minify($asset->getContent()));
    }
}
