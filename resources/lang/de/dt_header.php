<?php
return [
// Index DataTable Header
	// Auth
	'authusers.login_name' => 'Loginname',
	'authusers.first_name' => 'Vorname',
	'authusers.last_name' => 'Nachname',
	'authrole.name' => 'Name',
	// GuiLog
	'guilog.created_at' => 'Zeitpunkt',
	'guilog.username' => 'Nutzer',
	'guilog.method' => 'Aktion',
	'guilog.model' => 'Model',
	'guilog.model_id' => 'Model ID',
	// Company
	'company.name' => 'Unternehmen',
	'company.city' => 'Stadt',
	'company.phone' => 'Telefonnummer',
	'company.mail' => 'E-Mail',
	// Costcenter
	'costcenter.name' => 'Kostenstelle',
	'costcenter.number' => 'Nummer',
	//Invoices
	'invoice.type' => 'Typ',
	'invoice.year' => 'Jahr',
	'invoice.month' => 'Monat',
	//Item //**

	// Product
	'product.type' => 'Typ',
	'product.name' => 'Produkt',
	'product.price' => 'Preis',
	// Salesman
	'salesman.id' => 'ID',
	'salesman.lastname' => 'Nachname',
	'salesman.firstname' => 'Vorname',
	// SepaAccount
	'sepaaccount.name' => "Kontoname",
	'sepaaccount.institute' => 'Bank',
	'sepaaccount.iban' => 'IBAN',
	// SepaMandate
	'sepamandate.sepa_holder' => 'Kontoinhaber',
	'sepamandate.sepa_valid_from' => 'Gültig ab',
	'sepamandate.sepa_valid_to' => 'Gültig bis',
	'sepamandate.reference' => 'Kontoreferenz',
	// SettlementRun
	'settlementrun.year' => 'Jahr',
	'settlementrun.month' => 'Monat',
	'settlementrun.created_at' => 'Erstellt am',
	'verified' => 'Überprüft?',
	// MPR
	'mpr.name' => 'Name',
	// NetElement
	'netelement.id' => 'ID',
	'netelement.name' => 'Netzelement',
	'netelement.ip' => 'IP Adresse',
	'netelement.state' => 'Status',
	'netelement.pos' => 'Position',
	// NetElementType
	'netelementtype.name' => 'Netzelementtyp',
	//HfcSnmp
	'parameter.oid.name' => 'OID Name',
	//Mibfile
	'mibfile.id' => 'ID',
	'mibfile.name' => 'Mibfilename',
	'mibfile.version' => 'Version',
	// OID
	'oid.name_gui' => 'GUI Beschriftung',
	'oid.name' => 'OID Name',
	'oid.oid' => 'OID',
	'oid.access' => 'Schreibschutz',
	//SnmpValue
	'snmpvalue.oid_index' => 'OID Index',
	'snmpvalue.value' => 'OID Wert',
	// MAIL
	'email.localpart' => 'Lokalteil',
	'email.index' => 'Primäre E-Mail Adresse',
	'email.greylisting' => 'Greylisting Aktiv?',
	'email.blacklisting' => 'E-Mail auf Blacklist?',
	'email.forwardto' => 'Weiterleiten an:',
	// CMTS
	'cmts.id' => 'ID',
	'cmts.hostname' => 'Hostname',
	'cmts.ip' => 'IP',
	'cmts.company' => 'Hersteller',
	'cmts.type' => 'Typ',
	// Contract
	'contract.company' => 'Firma',
	'contract.number' => 'Nummer',
	'contract.firstname' => 'Vorname',
	'contract.lastname' => 'Nachname',
	'contract.zip' => 'PLZ',
	'contract.city' => 'Stadt',
	'contract.street' => 'Straße',
	'contract.house_number' => 'Hausnr',
	'contract.district' => 'Bezirk',
	'contract.contract_start' => 'Beginn',
	'contract.contract_end' => 'Ende',
	// Domain
	'domain.name' => 'Domain',
	'domain.type' => 'Typ',
	'domain.alias' => 'Alias',
	// Endpoint
	'endpoint.hostname' => 'Hostname',
	'endpoint.mac' => 'MAC',
	'endpoint.description' => 'Beschreibung',
	// IpPool
	'ippool.id' => 'ID',
	'ippool.type' => 'Typ',
	'ippool.net' => 'Netz',
	'ippool.netmask' => 'Netzmaske',
	'ippool.router_ip' => 'Router IP',
	'ippool.description' => 'Beschreibung',
	// Modem
	'modem.id' => 'Nummer',
	'modem.house_number' => 'Hausnr',
	'modem.mac' => 'MAC Adresse',
	'modem.name' => 'Modemname',
	'modem.lastname' => 'Nachname',
	'modem.firstname' => 'Vorname',
	'modem.city' => 'Stadt',
	'modem.street' => 'Straße',
	'modem.us_pwr' => 'US Pegel',
	'modem.district' => 'Bezirk',
	'contract_valid' => 'Vertrag gültig?',
	// QoS
	'qos.name' => 'QoS Name',
	'qos.ds_rate_max' => 'Maximale DS Geschwindigkeit',
	'qos.us_rate_max' => 'Maximale US Geschwindigkeit',
	// Mta
	'mta.hostname' => 'Hostname',
	'mta.mac' => 'MAC-Adresse',
	'mta.type' => 'Provisionierungstyp',
	// Configfile
	'configfile.name' => 'Konfiguartionsdatei',
	// PhonebookEntry
	'phonebookentry.id' => 'ID',
	// Phonenumber
	'phonenumber.prefix_number' => 'Vorwahl',
	'phonenumber.number' => 'Nummer',
	'phonenr_act' => 'Aktivierungsdatum',
	'phonenr_deact' => 'Deaktivierungsdatum',
	'phonenr_state' => 'Status',
	// Phonenumbermanagement
	'phonenumbermanagement.id' => 'ID',
	'phonenumbermanagement.activation_date' => 'Aktivierungsdatum',
	'phonenumbermanagement.deactivation_date' => 'Deaktivierungsdatum',
	// PhoneTariff
	'phonetariff.name' => 'Telefontarif',
	'phonetariff.type' => 'Typ',
	'phonetariff.description' => 'Beschreibung',
	'phonetariff.voip_protocol' => 'VOIP Protokoll',
	'phonetariff.usable' => 'Verfügbar',
	// ENVIA enviaorder
	'enviaorder.ordertype'  => 'Bestelltyp',
	'enviaorder.orderstatus'  => 'Bestellstatus',
	'escalation_level' => 'Statuslevel',
	'enviaorder.created_at'  => 'Erstellt am',
	'enviaorder.updated_at'  => 'Bearbeitet am',
	'enviaorder.orderdate'  => 'Bestelldatum',
	'enviaorder_current'  => 'Bearbeitung notwendig?',
	//ENVIA Contract
	'enviacontract.envia_contract_reference' => 'envia TEL Vertragsreferenz',
	'enviacontract.state' => 'Status',
	'enviacontract.start_date' => 'Anfangsdatum',
	'enviacontract.end_date' => 'Enddatum',
	// CDR
	'cdr.calldate' => 'Anrufzeitpunkt',
	'cdr.caller' => 'Anrufer',
	'cdr.called' => 'Angerufener',
	'cdr.mos_min_mult10' => 'minimaler MOS',
	// Numberrange
	'numberrange.id' => 'ID',
	'numberrange.name' => 'Name',
	'numberrange.start' => 'Start',
	'numberrange.end' => 'Ende',
	'numberrange.prefix' => 'Präfix',
	'numberrange.suffix' => 'Suffix',
	'numberrange.type' => 'Typ',
	'numberrange.costcenter.name' => 'Kostenstelle',
	// Ticket
	'ticket.id' => 'ID',
	'ticket.name' => 'Titel',
	'ticket.type' => 'Typ',
	'ticket.priority' => 'Priorität',
	'ticket.state' => 'Status',
	'ticket.user_id' => 'Erstellt von',
	'ticket.created_at' => 'Erstellt am',
	'ticket.assigned_users' => 'Bearbeiter',
];
