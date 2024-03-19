<?php

/*
 * SPDX-FileCopyrightText: 2022 Andreas Palm
 *
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Xentral\Carrier\SendCloud\Data;

class ParcelCreation extends ParcelBase
{
  public ?int $SenderAddressId = null;

//  const DEFAUlT_LENGTH = 150;

  public function toApiRequest(): array
  {
  
    define("DEFAULT_LENGTH", 150);
  
    $data = [
        'name' => substr($this->Name, 0, 75),
        'company_name' => substr($this->CompanyName, 0, 50),
        'address' => substr($this->Address, 0, 75),
        'address_2' => substr($this->Address2, 0, 75),
        'house_number' => substr($this->HouseNumber, 0, 20),
        'city' => substr($this->City, 0, 30),
        'postal_code' => substr($this->PostalCode, 0, 12),
        'telephone' => substr($this->Telephone, 0, 20),
        'request_label' => $this->RequestLabel,
        'email' => substr($this->EMail, 0, DEFAULT_LENGTH),
        'country' => substr($this->Country, 0, DEFAULT_LENGTH),
        'shipment' => ['id' => $this->ShippingMethodId],
        'weight' => number_format($this->Weight / 1000, 3, '.', null),
        'order_number' => substr($this->OrderNumber, 0, 50),
        'total_order_value_currency' => $this->TotalOrderValueCurrency,
        'total_order_value' => number_format($this->TotalOrderValue, 2, '.', null),
        'country_state' => substr($this->CountryState, 0, DEFAULT_LENGTH),
        'sender_address' => substr($this->SenderAddressId, 0, DEFAULT_LENGTH),
        'external_reference' => substr($this->ExternalReference, 0, DEFAULT_LENGTH),
        'total_insured_value' => $this->TotalInsuredValue ?? 0,
        'parcel_items' => array_map(fn(ParcelItem $item) => $item->toApiRequest(), $this->ParcelItems),
        'is_return' => $this->IsReturn,
        'length' => $this->Length,
        'width' => $this->Width,
        'height' => $this->Height,
    ];
    if ($this->CustomsInvoiceNr !== null) {
      $data['customs_invoice_nr'] = substr($this->CustomsInvoiceNr, 0, 40);
    }
    if ($this->CustomsShipmentType !== null) {
      $data['customs_shipment_type'] = substr($this->CustomsShipmentType, 0, DEFAULT_LENGTH);
    }
      
    return $data;
  }
}
