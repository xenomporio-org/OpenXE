<?xml version="1.0" encoding="UTF-8"?>
<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
             xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
             xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</cbc:CustomizationID>
    <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>
    <cbc:ID>{$rechnung.belegnr}</cbc:ID>
    <cbc:IssueDate>{$rechnung.datum}</cbc:IssueDate>
    <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>
    <cbc:Note>#ADU#Es gelten unsere Allgem. Geschäftsbedingungen, die Sie unter […] finden.</cbc:Note>
    <cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>
    <cbc:BuyerReference>04011000-12345-03</cbc:BuyerReference>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cbc:EndpointID schemeID="EM">seller@email.de</cbc:EndpointID>
            <cac:PartyName>
                <cbc:Name>[Seller trading name]</cbc:Name>
            </cac:PartyName>
            <cac:PostalAddress>
                <cbc:StreetName>[Seller address line 1]</cbc:StreetName>
                <cbc:CityName>[Seller city]</cbc:CityName>
                <cbc:PostalZone>12345</cbc:PostalZone>
                <cac:Country>
                    <cbc:IdentificationCode>DE</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>DE 123456789</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>[Seller name]</cbc:RegistrationName>
                <cbc:CompanyID>[HRA-Eintrag]</cbc:CompanyID>
                <cbc:CompanyLegalForm>123/456/7890, HRA-Eintrag in […]</cbc:CompanyLegalForm>
            </cac:PartyLegalEntity>
            <cac:Contact>
                <cbc:Name>nicht vorhanden</cbc:Name>
                <cbc:Telephone>+49 1234-5678</cbc:Telephone>
                <cbc:ElectronicMail>seller@email.de</cbc:ElectronicMail>
            </cac:Contact>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cbc:EndpointID schemeID="EM">buyer@info.de</cbc:EndpointID>
            <cac:PartyIdentification>
                <cbc:ID>[Buyer identifier]</cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
                <cbc:StreetName>[Buyer address line 1]</cbc:StreetName>
                <cbc:CityName>[Buyer city]</cbc:CityName>
                <cbc:PostalZone>12345</cbc:PostalZone>
                <cac:Country>
                    <cbc:IdentificationCode>DE</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>[Buyer name]</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:PaymentMeans>
        <cbc:PaymentMeansCode>58</cbc:PaymentMeansCode>
        <cac:PayeeFinancialAccount>
            <!-- dies ist eine nicht existerende aber valide IBAN als test dummy -->
            <cbc:ID>DE75512108001245126199</cbc:ID>
        </cac:PayeeFinancialAccount>
    </cac:PaymentMeans>
    <cac:PaymentTerms>
        <cbc:Note>Zahlbar sofort ohne Abzug.</cbc:Note>
    </cac:PaymentTerms>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="EUR">22.04</cbc:TaxAmount>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="EUR">314.86</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="EUR">22.04</cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID>S</cbc:ID>
                <cbc:Percent>7</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="EUR">$rechnung.10.17</cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="EUR">314.86</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="EUR">336.9</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="EUR">336.9</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    {foreach from=$rechnung.positionen item=position}
        <cac:InvoiceLine>
            <cbc:ID>{$position.bezeichnung}</cbc:ID>
            <cbc:Note>Die letzte Lieferung im Rahmen des abgerechneten Abonnements erfolgt in 12/2016 Lieferung erfolgt / erfolgte direkt vom Verlag</cbc:Note>
            <cbc:InvoicedQuantity unitCode="XPP">{$position.menge}</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="{$position.waehrung}">{$position.umsatz_netto_gesamt|string_format:"%.2f"}</cbc:LineExtensionAmount>
            <cac:InvoicePeriod>
                <cbc:StartDate>2016-01-01</cbc:StartDate>
                <cbc:EndDate>2016-12-31</cbc:EndDate>
            </cac:InvoicePeriod>
            <cac:OrderLineReference>
                <cbc:LineID>6171175.1</cbc:LineID>
            </cac:OrderLineReference>
            <cac:Item>
                <cbc:Description>Zeitschrift Inland</cbc:Description>
                <cbc:Name>Zeitschrift [...]</cbc:Name>
                <cac:SellersItemIdentification>
                    <cbc:ID>246</cbc:ID>
                </cac:SellersItemIdentification>
                <cac:CommodityClassification>
                    <cbc:ItemClassificationCode listID="IB">0721-880X</cbc:ItemClassificationCode>
                </cac:CommodityClassification>
                <cac:ClassifiedTaxCategory>
                    <cbc:ID>S</cbc:ID>
                    <cbc:Percent>7</cbc:Percent>
                    <cac:TaxScheme>
                        <cbc:ID>VAT</cbc:ID>
                    </cac:TaxScheme>
                </cac:ClassifiedTaxCategory>
            </cac:Item>
            <cac:Price>
                <cbc:PriceAmount currencyID="{$position.waehrung}">{$position.umsatz_netto_gesamt|string_format:"%.2f"}</cbc:PriceAmount>
            </cac:Price>
        </cac:InvoiceLine>
    {/foreach}                
</ubl:Invoice>
