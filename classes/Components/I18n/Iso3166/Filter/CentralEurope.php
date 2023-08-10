<?php

declare(strict_types=1);

namespace Xentral\Components\I18n\Iso3166\Filter;

use Xentral\Components\I18n\Dataaccess\DataFilterInterface;
use Xentral\Components\I18n\Iso3166\Key;


/**
 * Applies a filter to only select central european countries.
 *
 * @see      Custom
 * @see      \Xentral\Components\I18n\Dataaccess\DataFilter
 * @see      \Xentral\Components\I18n\Dataaccess\DataFilterInterface
 * @author   Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class CentralEurope extends Custom implements DataFilterInterface
{
    /**
     * Countries in Europe.
     *
     * @var array
     */
    const CentralEurope_Countries = ['CHE', 'DEU', 'AUT', 'ITA', 'FRA', 'ESP', 'PRT', 'GBR'];
    
    
    
    /**
     * Set predefined values.
     */
    public function __construct()
    {
        parent::__construct(static::CentralEurope_Countries, Key::ALPHA_3);
    }
}