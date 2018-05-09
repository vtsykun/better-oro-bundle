<?php

namespace Okvpn\Bundle\BetterOroBundle\Translation;

use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;

class OkvpnTranslationAdapter extends OroTranslationAdapter
{
    /**
     * {@inheritdoc}
     */
    public function request($uri, $data = [], $method = 'GET', $options = [], $headers = [])
    {
        throw new \BadMethodCallException(
            "OroTranslationAdapter#request() was removed, because it send the security sensitive information to " .
            "'http://translations.orocrm.com/api' host. To enable load translation, please update " .
            "app/config/config.yml with options: okvpn_better_oro->capabilities->disable_remote_transactions: false"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchStatistic(array $packages = [])
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function download($path, array $projects = [], $package = null)
    {
        return false;
    }
}
