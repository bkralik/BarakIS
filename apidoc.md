# LicServ API
Primární API pro komunikaci s PlusSystem aplikacemi.

## Endpoint `/api/get-cislo` - Získání instalačního čísla
Dotaz určený pro získání jednoho instalačního čísla.

### Dotaz
```
POST /api/get-cislo
{
  "jmeno":"Petr Králík 3",
  "telefon":"",
  "email":"",
  "partner":""
}
```
 * Položka __jmeno__ je povinná, ostatní jsou volitelné.

### Odpověď

```
{
  "error": false,
  "status": 0,
  "status_text": "Bylo vám přiděleno instalační číslo FiiNGxUSWD"
}
```

### Odpověď v případě chyby
```
{
  "error": true,
  "status": 1,
  "status_text": "Jméno musí být zadáno."
}
```

## Endpoint `/api/send-usage` - Uložení dat o využití programu
Dotaz, který ukládá (nebo zaktualizuje) data o využití daný den. Při prvním POSTu (daný den) dojde k uložení nového záznamu v databázi, v dalším postu (daný den) dojde pouze k aktualizaci údajů. 

### Dotaz
```
POST /api/send-usage
{
  "instalacniCislo":"e47a1-9ffdb",
  "cas":5588833113,
  "var1":165.8,
  "var2":172.3
}
```
 * Položka __instalacniCislo__ je povinná, ostatní jsou volitelné. Pokud je zadán __cas__ (unix timestamp), použije se ten, jinak se bere aktuální čas v moment POSTu.
 * Ukládají se tři _double_ proměnné __var1__ až __var3__. Pokud není v dotazu proměnná specifikována (program nemá tolik diagnostických výstupů), uloží se jako _NULL_ a při dalším zpracování je ignorována.

### Odpověď

```
{
  "error": false,
  "status": 0,
  "status_text": "Data o využití byla k licenci vložena v pořádku."
}
```

### Odpověď v případě chyby
```
{
  "error": true,
  "status": 1,
  "status_text": "Licenční klíč je neplatný."
}
```

## Endpoint `/api/check-licence` - Kontrola licence
Hlavní dotaz, slouží k ověření licenčního čísla, aktuální licence, aktuálnosti hardware a zároveň i k update licenčních podrobností v databázi.

### Dotaz

```
POST /api/check-licence
{
  "instalacniCislo":"e47a1-9ffdb",
  "identifikaceHW":"sdfdgsdgsdfg",
  "variantaPohody":1,
  "aplikace":1,
  "verzeAplikace":"1.5.6",
  "zakaznik":{
    "jmeno":"KSoft - Karel Novák",
    "ulice":"Krátká 159",
    "mesto":"Polička",
    "psc":"128 64",
    "telefon":"",
    "email":"",
    "ico":""
  }
}
```

 * Položky __variantaPohody__ a __aplikace__ jsou integery z číselníku.
 * Všechny položky pole __zakaznik__ jsou volitelné. Pole však musí být přítomné! Pokud licence nemá ještě v databázi přiřazeného zákazníka, uloží se __zakaznik__ při každém dotazu do databáze.
 * Položka __verzeAplikace__ je volitelná, pokud je přítomná, pak je uložena do databáze a zobrazena u licence.

### Odpověď

```
{
  "error": false,
  "status": 0,
  "status_text": "Licence je v pořádku.",
  "checksum": "2e6169e4acf1ce9a7a17324ab3d4467e92740132",
  "licence": {
    "platnostLicence": 1439900672,
    "platnostServisu": 1440885600,
    "var1": 100,
    "var2": null,
    "var3": null,
    "identifikaceHW": "sdfdgsdgsdfg",
    "instalacniCislo": "e47a1-9ffdb",
    "variantaPohody": 1,
    "typLicence": 2,
    "aplikace": 1,
    "partner": "KarelSoft",
    "platnychLicenci": 20,
    "variantaAplikace": 2
  },
  "aktualizace": {
    "aktualizaceAutomaticky": 0,
    "aktualizaceNaVerzi": "1.6.4"
  },
  "zakaznik": {
    "jmeno": "bSoft - Petr Bartoň",
    "ulice": "Falešná 231",
    "mesto": "Hradec Králové 6",
    "psc": "500 06",
    "telefon": "496633634",
    "email": "bSoft@bsoft.cz",
    "ico": 9756431,
    "dic": null
  }
}
```
 * Pole __zakaznik__ je vráceno pouze v případě, že licence je v databázi navázána na zákazníka. Taktéž položka __partner__ je vrácena pouze pokud licence má přiřazeného partnera.
 * Pole __aktualizaceNaVerzi__ je vráceno pouze v případě, že v webovém rozhraní byl zadán požadavek aktualizace aplikace na určitou verzi. Pole se po dotazu vymaže (ukáže se tedy jenom jednou).
 * Položka __checksum__ obsahuje kontrolní kód licence. V případě, že kontrolní kód vypočtený v aplikaci neodpovídá kódu v odpovědi, měla by licence být odmítnuta - pravděpodobně se s ní útočník snaží manipulovat.

### Výpočet kontroního kodu

```php
public function getChecksum($lic) {
  $key = "tajnyahlavneneverejnyklic";
  
  $checksum = sha1(
    $lic->platnostLicence .
    $lic->identifikaceHW .
    $lic->instalacniCislo->cislo .
    $lic->aplikace->id .
    $lic->variantaPohody->id .
    $key
  );
  
  return($checksum);
}
```

### Odpověď v případě chyby

```
{
  "error":true,
  "status":10,
  "status_text":"Licenční klíč je neplatný."
}
```

### Mechanismus funkce

#### Kontrola - Klíč není v instalacniCislo
 * _neplatný klíč_
 * poslat `error 10`

#### Kontrola - Klíč je v instalacnCislo, není k němu licence, HW ale už existuje
 * _někdo si snaží udělat novou licenci k existujícímu stroji_
 * poslat `error 11`

#### Kontrola - Klíč je v instalacniCislo, není licence, HW neexistuje
 * _testovací licence_
 * vytvořit licenci s platností 14 dní
 * poslat licenci
 
#### Kontrola - Klíč je v instalacniCislo, je licence, platnostLicence propadlá
 * _někdo se snaží spustit aplikaci s prošlou licencí_
 * poslat `error 12`

#### Kontrola - Klíč je v instalacniCislo, je licence, platnostLicence OK, aplikace nesedí
 * _někdo se snaží spustit aplikaci s licencí pro jinou aplikaci_
 * poslat `error 13`

#### Kontrola - Klíč je v instalacniCislo, je licence, platnostLicence OK, HW sedí
 * _běžné spuštění_
 * poslat licenci

#### Kontrola - Klíč je v instalacniCislo, je licence, platnostLicence OK, HW nesedí
 * _změna hardware serveru_
 * změnit platnost licence na 5 dní
 * poslat licenci s upozorněním


### Status
| status | status_text                                               |
| ------ | --------------------------------------------------------- |
| 0      | Licence je v pořádku.                                     |
| 1      | Byla vytvořena zkušební čtrnáctidenní licence.            |
| 2      | Došlo ke změně hardware, licence byla omezena na pět dní. |
|        |                                                           |
| 10     | Licenční klíč je neplatný.                                |
| 11     | Tento hardware má již přidělenou jinou licenci.           |
| 12     | Zadaná licence vypršela.                                  |
| 13     | Licenční klíč nepřísluší k této aplikaci.                 |
|        |                                                           |
| 20     | Licenční klíč musí být zadán!                             |
| 21     | ID hardware musí být zadáno!                              |
| 22     | VariantaPohody a Aplikace musí být zadána!                |
| 23     | VariantaPohody nebo Aplikace není validní.                |
| 24     | Zakaznik musí být zadán.                                  |

Statusy 0-9 značí úspěšné získání licence, 10-19 značí chybu v licencování a 20-29 ukazují na chybu v komunikaci s API.