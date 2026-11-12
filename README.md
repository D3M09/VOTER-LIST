# VOTER-LIST

Voter-list PDFs from Sonatola converted into structured JSON. One extractor script, one GitHub Actions pipeline, no manual steps.

## Layout

```
pdf/          source PDFs
json/         generated JSON, written and committed by the workflow
pdf2json.py   extractor
jsonmap.json  glyph ID to Unicode map for the embedded Bengali font
README.md
```

## Pipeline

`.github/workflows/convert.yml` runs on:

- pushes to `main` that touch `pdf/`, `pdf2json.py` or `jsonmap.json`
- manual `workflow_dispatch`

Steps: checkout, Python 3.12, `pip install pypdf==6.21.4`, `python pdf2json.py -o json`, commit `json/` back to the repo with `[skip ci]`. Re-running is safe, outputs are overwritten.

## Local usage

```
pip install pypdf==6.21.4
python pdf2json.py
```

With no arguments the script walks `pdf/` and writes every `json/<name>.json`. You can also target files directly:

```
python pdf2json.py pdf/101866_com_321_male_without_photo_19_2025-11-24.pdf -o json
```

## Output schema

```
{
  "file": "...pdf",
  "pages": 21,
  "meta": { "region", "district", "upazila", "union", "post_office", "area_code",
            "area_name", "area_no", "postcode", "ward_no", "total_voters",
            "male_voters", "female_voters", "publish_date", "city_corp_or_pourasabha" },
  "records": [
    { "serial", "serial_bn", "name", "voter_no", "voter_no_digits", "father",
       "mother", "occupation", "dob", "address", "page", "column", "status" }
  ],
  "stats": { "records", "active", "first_serial", "last_serial", "cancelled",
             "duplicates", "missing_serials", "incomplete" }
}
```

`status` is `ok` for active voters and `cancelled` for serials that appear without details.

## jsonmap.json

The PDFs embed a subset font, so text is stored as glyph IDs, not Unicode. `jsonmap.json` maps each glyph ID to its Bengali character or conjunct. Unknown IDs decode to `U+FFFD`. Update this file when new glyphs appear, then re-run the workflow.

## Notes

The source set held 406 PDFs; 98 were byte-identical copies under `WARD NO-09/`, leaving 308 unique files. `pdf/` contains the deduplicated set and the workflow regenerates `json/` from it.

## Coverage

308 PDFs, 154 areas, 10,474 pages, 174,002 records.

| Union / Pourashava | Areas | PDFs | Records |
|---|---:|---:|---:|
| Balua | 27 | 54 | 23,568 |
| Digdair | 22 | 44 | 26,874 |
| Jorgachha | 22 | 44 | 32,584 |
| Madhupur | 13 | 26 | 18,793 |
| Pakulla | 16 | 32 | 21,970 |
| Sonatola | 19 | 38 | 14,449 |
| Tekani Chukaina | 14 | 28 | 14,103 |
| Sonatola Pourashava (সোনাতলা পৌরসভা) | 21 | 42 | 21,661 |

## PDF and JSON map

### Balua

27 areas, 54 files, 23,568 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101866` | বাগবাড়ী | 21 | 323 | 321 | [PDF](pdf/101866_com_321_male_without_photo_19_2025-11-24.pdf) | [JSON](json/101866_com_321_male_without_photo_19_2025-11-24.json) |
| 2 | `101866` | বাগবাড়ী | 22 | 347 | 345 | [PDF](pdf/101866_com_345_female_without_photo_20_2025-11-24.pdf) | [JSON](json/101866_com_345_female_without_photo_20_2025-11-24.json) |
| 3 | `101867` | কুশারঘোপ | 17 | 264 | 263 | [PDF](pdf/101867_com_263_female_without_photo_15_2025-11-24.pdf) | [JSON](json/101867_com_263_female_without_photo_15_2025-11-24.json) |
| 4 | `101867` | কুশারঘোপ | 17 | 266 | 265 | [PDF](pdf/101867_com_265_male_without_photo_15_2025-11-24.pdf) | [JSON](json/101867_com_265_male_without_photo_15_2025-11-24.json) |
| 5 | `101868` | চক সৈয়দপুর | 12 | 169 | 168 | [PDF](pdf/101868_com_168_female_without_photo_10_2025-11-24.pdf) | [JSON](json/101868_com_168_female_without_photo_10_2025-11-24.json) |
| 6 | `101868` | চক সৈয়দপুর | 12 | 175 | 175 | [PDF](pdf/101868_com_175_male_without_photo_10_2025-11-24.pdf) | [JSON](json/101868_com_175_male_without_photo_10_2025-11-24.json) |
| 7 | `101869` | জনার্দনপুর | 11 | 147 | 147 | [PDF](pdf/101869_com_147_female_without_photo_9_2025-11-24.pdf) | [JSON](json/101869_com_147_female_without_photo_9_2025-11-24.json) |
| 8 | `101869` | জনার্দনপুর | 11 | 149 | 148 | [PDF](pdf/101869_com_148_male_without_photo_9_2025-11-24.pdf) | [JSON](json/101869_com_148_male_without_photo_9_2025-11-24.json) |
| 9 | `101870` | পুরকায়েত | 15 | 231 | 231 | [PDF](pdf/101870_com_231_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101870_com_231_female_without_photo_13_2025-11-24.json) |
| 10 | `101870` | পুরকায়েত | 16 | 232 | 231 | [PDF](pdf/101870_com_231_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101870_com_231_male_without_photo_14_2025-11-24.json) |
| 11 | `101871` | মোকন্দপুর | 6 | 65 | 65 | [PDF](pdf/101871_com_65_female_without_photo_4_2025-11-24.pdf) | [JSON](json/101871_com_65_female_without_photo_4_2025-11-24.json) |
| 12 | `101871` | মোকন্দপুর | 7 | 74 | 74 | [PDF](pdf/101871_com_74_male_without_photo_5_2025-11-24.pdf) | [JSON](json/101871_com_74_male_without_photo_5_2025-11-24.json) |
| 13 | `101872` | রাখালগাছী | 15 | 225 | 225 | [PDF](pdf/101872_com_225_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101872_com_225_female_without_photo_13_2025-11-24.json) |
| 14 | `101872` | রাখালগাছী | 16 | 245 | 245 | [PDF](pdf/101872_com_245_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101872_com_245_male_without_photo_14_2025-11-24.json) |
| 15 | `101873` | গবারপাড়া | 27 | 439 | 439 | [PDF](pdf/101873_com_439_female_without_photo_25_2025-11-24.pdf) | [JSON](json/101873_com_439_female_without_photo_25_2025-11-24.json) |
| 16 | `101873` | গবারপাড়া | 28 | 448 | 442 | [PDF](pdf/101873_com_442_male_without_photo_26_2025-11-24.pdf) | [JSON](json/101873_com_442_male_without_photo_26_2025-11-24.json) |
| 17 | `101874` | ছোট বালুয়া | 44 | 742 | 738 | [PDF](pdf/101874_com_738_male_without_photo_42_2025-11-24.pdf) | [JSON](json/101874_com_738_male_without_photo_42_2025-11-24.json) |
| 18 | `101874` | ছোট বালুয়া | 44 | 748 | 747 | [PDF](pdf/101874_com_747_female_without_photo_42_2025-11-24.pdf) | [JSON](json/101874_com_747_female_without_photo_42_2025-11-24.json) |
| 19 | `101875` | দীঘিরপাড়া | 19 | 297 | 295 | [PDF](pdf/101875_com_295_male_without_photo_17_2025-11-24.pdf) | [JSON](json/101875_com_295_male_without_photo_17_2025-11-24.json) |
| 20 | `101875` | দীঘিরপাড়া | 20 | 312 | 312 | [PDF](pdf/101875_com_312_female_without_photo_18_2025-11-24.pdf) | [JSON](json/101875_com_312_female_without_photo_18_2025-11-24.json) |
| 21 | `101876` | আগাবরগাছা | 31 | 511 | 506 | [PDF](pdf/101876_com_506_male_without_photo_29_2025-11-24.pdf) | [JSON](json/101876_com_506_male_without_photo_29_2025-11-24.json) |
| 22 | `101876` | আগাবরগাছা | 32 | 537 | 537 | [PDF](pdf/101876_com_537_female_without_photo_30_2025-11-24.pdf) | [JSON](json/101876_com_537_female_without_photo_30_2025-11-24.json) |
| 23 | `101877` | উত্তর আটকরিয়া | 52 | 887 | 878 | [PDF](pdf/101877_com_878_male_without_photo_50_2025-11-24.pdf) | [JSON](json/101877_com_878_male_without_photo_50_2025-11-24.json) |
| 24 | `101877` | উত্তর আটকরিয়া | 53 | 901 | 897 | [PDF](pdf/101877_com_897_female_without_photo_51_2025-11-24.pdf) | [JSON](json/101877_com_897_female_without_photo_51_2025-11-24.json) |
| 25 | `101878` | ঝিনারপাড়া | 13 | 178 | 177 | [PDF](pdf/101878_com_177_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101878_com_177_female_without_photo_11_2025-11-24.json) |
| 26 | `101878` | ঝিনারপাড়া | 13 | 193 | 193 | [PDF](pdf/101878_com_193_male_without_photo_11_2025-11-24.pdf) | [JSON](json/101878_com_193_male_without_photo_11_2025-11-24.json) |
| 27 | `101879` | দক্ষিণ আটকরিয়া | 72 | 1,257 | 1,251 | [PDF](pdf/101879_com_1251_male_without_photo_70_2025-11-24.pdf) | [JSON](json/101879_com_1251_male_without_photo_70_2025-11-24.json) |
| 28 | `101879` | দক্ষিণ আটকরিয়া | 73 | 1,270 | 1,269 | [PDF](pdf/101879_com_1269_female_without_photo_71_2025-11-24.pdf) | [JSON](json/101879_com_1269_female_without_photo_71_2025-11-24.json) |
| 29 | `101880` | বামনিয়া | 22 | 347 | 344 | [PDF](pdf/101880_com_344_male_without_photo_20_2025-11-24.pdf) | [JSON](json/101880_com_344_male_without_photo_20_2025-11-24.json) |
| 30 | `101880` | বামনিয়া | 22 | 346 | 346 | [PDF](pdf/101880_com_346_female_without_photo_20_2025-11-24.pdf) | [JSON](json/101880_com_346_female_without_photo_20_2025-11-24.json) |
| 31 | `101881` | উত্তর দিঘলকান্দি | 29 | 470 | 467 | [PDF](pdf/101881_com_467_male_without_photo_27_2025-11-24.pdf) | [JSON](json/101881_com_467_male_without_photo_27_2025-11-24.json) |
| 32 | `101881` | উত্তর দিঘলকান্দি | 31 | 509 | 508 | [PDF](pdf/101881_com_508_female_without_photo_29_2025-11-24.pdf) | [JSON](json/101881_com_508_female_without_photo_29_2025-11-24.json) |
| 33 | `101882` | বালুয়াপাড়া | 17 | 256 | 256 | [PDF](pdf/101882_com_256_male_without_photo_15_2025-11-24.pdf) | [JSON](json/101882_com_256_male_without_photo_15_2025-11-24.json) |
| 34 | `101882` | বালুয়াপাড়া | 18 | 280 | 280 | [PDF](pdf/101882_com_280_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101882_com_280_female_without_photo_16_2025-11-24.json) |
| 35 | `101883` | হাড়িয়াকান্দি | 40 | 672 | 669 | [PDF](pdf/101883_com_669_male_without_photo_38_2025-11-24.pdf) | [JSON](json/101883_com_669_male_without_photo_38_2025-11-24.json) |
| 36 | `101883` | হাড়িয়াকান্দি | 41 | 693 | 691 | [PDF](pdf/101883_com_691_female_without_photo_39_2025-11-24.pdf) | [JSON](json/101883_com_691_female_without_photo_39_2025-11-24.json) |
| 37 | `101884` | পাতিলাকুড়া | 18 | 277 | 277 | [PDF](pdf/101884_com_277_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101884_com_277_female_without_photo_16_2025-11-24.json) |
| 38 | `101884` | পাতিলাকুড়া | 18 | 279 | 277 | [PDF](pdf/101884_com_277_male_without_photo_16_2025-11-24.pdf) | [JSON](json/101884_com_277_male_without_photo_16_2025-11-24.json) |
| 39 | `101885` | রশিদপুর | 50 | 858 | 851 | [PDF](pdf/101885_com_851_male_without_photo_48_2025-11-24.pdf) | [JSON](json/101885_com_851_male_without_photo_48_2025-11-24.json) |
| 40 | `101885` | রশিদপুর | 50 | 855 | 855 | [PDF](pdf/101885_com_855_female_without_photo_48_2025-11-24.pdf) | [JSON](json/101885_com_855_female_without_photo_48_2025-11-24.json) |
| 41 | `101886` | রশিনাবাড়ী | 17 | 262 | 260 | [PDF](pdf/101886_com_260_male_without_photo_15_2025-11-24.pdf) | [JSON](json/101886_com_260_male_without_photo_15_2025-11-24.json) |
| 42 | `101886` | রশিনাবাড়ী | 19 | 292 | 292 | [PDF](pdf/101886_com_292_female_without_photo_17_2025-11-24.pdf) | [JSON](json/101886_com_292_female_without_photo_17_2025-11-24.json) |
| 43 | `101887` | ধর্মকুল | 41 | 693 | 689 | [PDF](pdf/101887_com_689_female_without_photo_39_2025-11-24.pdf) | [JSON](json/101887_com_689_female_without_photo_39_2025-11-24.json) |
| 44 | `101887` | ধর্মকুল | 42 | 711 | 704 | [PDF](pdf/101887_com_704_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101887_com_704_male_without_photo_40_2025-11-24.json) |
| 45 | `101888` | পুগলিয়া | 23 | 366 | 362 | [PDF](pdf/101888_com_362_male_without_photo_21_2025-11-24.pdf) | [JSON](json/101888_com_362_male_without_photo_21_2025-11-24.json) |
| 46 | `101888` | পুগলিয়া | 24 | 387 | 387 | [PDF](pdf/101888_com_387_female_without_photo_22_2025-11-24.pdf) | [JSON](json/101888_com_387_female_without_photo_22_2025-11-24.json) |
| 47 | `101889` | মহিষাবাড়ী | 21 | 325 | 325 | [PDF](pdf/101889_com_325_female_without_photo_19_2025-11-24.pdf) | [JSON](json/101889_com_325_female_without_photo_19_2025-11-24.json) |
| 48 | `101889` | মহিষাবাড়ী | 21 | 333 | 332 | [PDF](pdf/101889_com_332_male_without_photo_19_2025-11-24.pdf) | [JSON](json/101889_com_332_male_without_photo_19_2025-11-24.json) |
| 49 | `101890` | নগরপাড়া | 26 | 427 | 427 | [PDF](pdf/101890_com_427_female_without_photo_24_2025-11-24.pdf) | [JSON](json/101890_com_427_female_without_photo_24_2025-11-24.json) |
| 50 | `101890` | নগরপাড়া | 27 | 438 | 437 | [PDF](pdf/101890_com_437_male_without_photo_25_2025-11-24.pdf) | [JSON](json/101890_com_437_male_without_photo_25_2025-11-24.json) |
| 51 | `101891` | বড় বালুয়া -১নং ও ৯নং | 40 | 674 | 668 | [PDF](pdf/101891_com_668_male_without_photo_38_2025-11-24.pdf) | [JSON](json/101891_com_668_male_without_photo_38_2025-11-24.json) |
| 52 | `101891` | বড় বালুয়া -১নং ও ৯নং | 42 | 708 | 704 | [PDF](pdf/101891_com_704_female_without_photo_40_2025-11-24.pdf) | [JSON](json/101891_com_704_female_without_photo_40_2025-11-24.json) |
| 53 | `101892` | সমজাতাইড় | 16 | 232 | 230 | [PDF](pdf/101892_com_230_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101892_com_230_male_without_photo_14_2025-11-24.json) |
| 54 | `101892` | সমজাতাইড় | 16 | 246 | 246 | [PDF](pdf/101892_com_246_female_without_photo_14_2025-11-24.pdf) | [JSON](json/101892_com_246_female_without_photo_14_2025-11-24.json) |

### Digdair

22 areas, 44 files, 26,874 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101893` | কলসদহ পাড়া | 24 | 392 | 392 | [PDF](pdf/101893_com_392_female_without_photo_22_2025-11-24.pdf) | [JSON](json/101893_com_392_female_without_photo_22_2025-11-24.json) |
| 2 | `101893` | কলসদহ পাড়া | 25 | 397 | 396 | [PDF](pdf/101893_com_396_male_without_photo_23_2025-11-24.pdf) | [JSON](json/101893_com_396_male_without_photo_23_2025-11-24.json) |
| 3 | `101894` | পাঠানপাড়া | 41 | 683 | 681 | [PDF](pdf/101894_com_681_female_without_photo_39_2025-11-24.pdf) | [JSON](json/101894_com_681_female_without_photo_39_2025-11-24.json) |
| 4 | `101894` | পাঠানপাড়া | 42 | 709 | 708 | [PDF](pdf/101894_com_708_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101894_com_708_male_without_photo_40_2025-11-24.json) |
| 5 | `101895` | মধ্যপাড়া | 13 | 178 | 178 | [PDF](pdf/101895_com_178_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101895_com_178_female_without_photo_11_2025-11-24.json) |
| 6 | `101895` | মধ্যপাড়া | 14 | 205 | 205 | [PDF](pdf/101895_com_205_male_without_photo_12_2025-11-24.pdf) | [JSON](json/101895_com_205_male_without_photo_12_2025-11-24.json) |
| 7 | `101896` | কোয়ালিকান্দী | 21 | 335 | 333 | [PDF](pdf/101896_com_333_female_without_photo_19_2025-11-24.pdf) | [JSON](json/101896_com_333_female_without_photo_19_2025-11-24.json) |
| 8 | `101896` | কোয়ালিকান্দী | 22 | 356 | 355 | [PDF](pdf/101896_com_355_male_without_photo_20_2025-11-24.pdf) | [JSON](json/101896_com_355_male_without_photo_20_2025-11-24.json) |
| 9 | `101897` | ফাজিলপুর | 42 | 709 | 705 | [PDF](pdf/101897_com_705_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101897_com_705_male_without_photo_40_2025-11-24.json) |
| 10 | `101897` | ফাজিলপুর | 43 | 726 | 724 | [PDF](pdf/101897_com_724_female_without_photo_41_2025-11-24.pdf) | [JSON](json/101897_com_724_female_without_photo_41_2025-11-24.json) |
| 11 | `101898` | লক্ষ্মীনারায়ণ পাড়া | 11 | 153 | 153 | [PDF](pdf/101898_com_153_female_without_photo_9_2025-11-24.pdf) | [JSON](json/101898_com_153_female_without_photo_9_2025-11-24.json) |
| 12 | `101898` | লক্ষ্মীনারায়ণ পাড়া | 12 | 166 | 166 | [PDF](pdf/101898_com_166_male_without_photo_10_2025-11-24.pdf) | [JSON](json/101898_com_166_male_without_photo_10_2025-11-24.json) |
| 13 | `101899` | বারঘরিয়া | 29 | 478 | 474 | [PDF](pdf/101899_com_474_male_without_photo_27_2025-11-24.pdf) | [JSON](json/101899_com_474_male_without_photo_27_2025-11-24.json) |
| 14 | `101899` | বারঘরিয়া | 30 | 501 | 499 | [PDF](pdf/101899_com_499_female_without_photo_28_2025-11-24.pdf) | [JSON](json/101899_com_499_female_without_photo_28_2025-11-24.json) |
| 15 | `101900` | কোয়ালিপাড়া | 29 | 477 | 472 | [PDF](pdf/101900_com_472_male_without_photo_27_2025-11-24.pdf) | [JSON](json/101900_com_472_male_without_photo_27_2025-11-24.json) |
| 16 | `101900` | কোয়ালিপাড়া | 30 | 498 | 496 | [PDF](pdf/101900_com_496_female_without_photo_28_2025-11-24.pdf) | [JSON](json/101900_com_496_female_without_photo_28_2025-11-24.json) |
| 17 | `101901` | মুলবাড়ী | 48 | 823 | 818 | [PDF](pdf/101901_com_818_female_without_photo_46_2025-11-24.pdf) | [JSON](json/101901_com_818_female_without_photo_46_2025-11-24.json) |
| 18 | `101901` | মুলবাড়ী | 51 | 868 | 865 | [PDF](pdf/101901_com_865_male_without_photo_49_2025-11-24.pdf) | [JSON](json/101901_com_865_male_without_photo_49_2025-11-24.json) |
| 19 | `101902` | চিল্লিপাড়া | 10 | 129 | 129 | [PDF](pdf/101902_com_129_female_without_photo_8_2025-11-24.pdf) | [JSON](json/101902_com_129_female_without_photo_8_2025-11-24.json) |
| 20 | `101902` | চিল্লিপাড়া | 10 | 131 | 131 | [PDF](pdf/101902_com_131_male_without_photo_8_2025-11-24.pdf) | [JSON](json/101902_com_131_male_without_photo_8_2025-11-24.json) |
| 21 | `101903` | দিগদাইড় | 60 | 1,034 | 1,028 | [PDF](pdf/101903_com_1028_male_without_photo_58_2025-11-24.pdf) | [JSON](json/101903_com_1028_male_without_photo_58_2025-11-24.json) |
| 22 | `101903` | দিগদাইড় | 62 | 1,061 | 1,058 | [PDF](pdf/101903_com_1058_female_without_photo_60_2025-11-24.pdf) | [JSON](json/101903_com_1058_female_without_photo_60_2025-11-24.json) |
| 23 | `101904` | নুরপুর | 19 | 301 | 300 | [PDF](pdf/101904_com_300_female_without_photo_17_2025-11-24.pdf) | [JSON](json/101904_com_300_female_without_photo_17_2025-11-24.json) |
| 24 | `101904` | নুরপুর | 19 | 302 | 300 | [PDF](pdf/101904_com_300_male_without_photo_17_2025-11-24.pdf) | [JSON](json/101904_com_300_male_without_photo_17_2025-11-24.json) |
| 25 | `101905` | কাতলাহার | 28 | 463 | 462 | [PDF](pdf/101905_com_462_female_without_photo_26_2025-11-24.pdf) | [JSON](json/101905_com_462_female_without_photo_26_2025-11-24.json) |
| 26 | `101905` | কাতলাহার | 29 | 477 | 473 | [PDF](pdf/101905_com_473_male_without_photo_27_2025-11-24.pdf) | [JSON](json/101905_com_473_male_without_photo_27_2025-11-24.json) |
| 27 | `101906` | গোনিয়ারীকান্দি | 32 | 521 | 519 | [PDF](pdf/101906_com_519_male_without_photo_30_2025-11-24.pdf) | [JSON](json/101906_com_519_male_without_photo_30_2025-11-24.json) |
| 28 | `101906` | গোনিয়ারীকান্দি | 33 | 553 | 551 | [PDF](pdf/101906_com_551_female_without_photo_31_2025-11-24.pdf) | [JSON](json/101906_com_551_female_without_photo_31_2025-11-24.json) |
| 29 | `101907` | মাদারীপাড়া | 22 | 351 | 351 | [PDF](pdf/101907_com_351_female_without_photo_20_2025-11-24.pdf) | [JSON](json/101907_com_351_female_without_photo_20_2025-11-24.json) |
| 30 | `101907` | মাদারীপাড়া | 23 | 367 | 364 | [PDF](pdf/101907_com_364_male_without_photo_21_2025-11-24.pdf) | [JSON](json/101907_com_364_male_without_photo_21_2025-11-24.json) |
| 31 | `101908` | নুরারপটল | 32 | 520 | 518 | [PDF](pdf/101908_com_518_female_without_photo_30_2025-11-24.pdf) | [JSON](json/101908_com_518_female_without_photo_30_2025-11-24.json) |
| 32 | `101908` | নুরারপটল | 34 | 557 | 555 | [PDF](pdf/101908_com_555_male_without_photo_32_2025-11-24.pdf) | [JSON](json/101908_com_555_male_without_photo_32_2025-11-24.json) |
| 33 | `101909` | মহিচরণ | 52 | 897 | 895 | [PDF](pdf/101909_com_895_female_without_photo_50_2025-11-24.pdf) | [JSON](json/101909_com_895_female_without_photo_50_2025-11-24.json) |
| 34 | `101909` | মহিচরণ | 53 | 915 | 912 | [PDF](pdf/101909_com_912_male_without_photo_51_2025-11-24.pdf) | [JSON](json/101909_com_912_male_without_photo_51_2025-11-24.json) |
| 35 | `101910` | লোহাগাড়া | 52 | 887 | 887 | [PDF](pdf/101910_com_887_female_without_photo_50_2025-11-24.pdf) | [JSON](json/101910_com_887_female_without_photo_50_2025-11-24.json) |
| 36 | `101910` | লোহাগাড়া | 52 | 890 | 888 | [PDF](pdf/101910_com_888_male_without_photo_50_2025-11-24.pdf) | [JSON](json/101910_com_888_male_without_photo_50_2025-11-24.json) |
| 37 | `101911` | উত্তর বঁাশহাটা | 36 | 609 | 607 | [PDF](pdf/101911_com_607_female_without_photo_34_2025-11-24.pdf) | [JSON](json/101911_com_607_female_without_photo_34_2025-11-24.json) |
| 38 | `101911` | উত্তর বঁাশহাটা | 37 | 614 | 609 | [PDF](pdf/101911_com_609_male_without_photo_35_2025-11-24.pdf) | [JSON](json/101911_com_609_male_without_photo_35_2025-11-24.json) |
| 39 | `101912` | উত্তর সুখানপুকুর | 28 | 464 | 460 | [PDF](pdf/101912_com_460_male_without_photo_26_2025-11-24.pdf) | [JSON](json/101912_com_460_male_without_photo_26_2025-11-24.json) |
| 40 | `101912` | উত্তর সুখানপুকুর | 30 | 496 | 495 | [PDF](pdf/101912_com_495_female_without_photo_28_2025-11-24.pdf) | [JSON](json/101912_com_495_female_without_photo_28_2025-11-24.json) |
| 41 | `101913` | চাড়ালকান্দি | 27 | 434 | 430 | [PDF](pdf/101913_com_430_male_without_photo_25_2025-11-24.pdf) | [JSON](json/101913_com_430_male_without_photo_25_2025-11-24.json) |
| 42 | `101913` | চাড়ালকান্দি | 27 | 447 | 446 | [PDF](pdf/101913_com_446_female_without_photo_25_2025-11-24.pdf) | [JSON](json/101913_com_446_female_without_photo_25_2025-11-24.json) |
| 43 | `101914` | শিহিপুর | 134 | 2,361 | 2,352 | [PDF](pdf/101914_com_2352_male_without_photo_132_2025-11-24.pdf) | [JSON](json/101914_com_2352_male_without_photo_132_2025-11-24.json) |
| 44 | `101914` | শিহিপুর | 138 | 2,439 | 2,432 | [PDF](pdf/101914_com_2432_female_without_photo_136_2025-11-24.pdf) | [JSON](json/101914_com_2432_female_without_photo_136_2025-11-24.json) |

### Jorgachha

22 areas, 44 files, 32,584 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101915` | শিচারপাড়া | 71 | 1,228 | 1,216 | [PDF](pdf/101915_com_1216_male_without_photo_69_2025-11-24.pdf) | [JSON](json/101915_com_1216_male_without_photo_69_2025-11-24.json) |
| 2 | `101915` | শিচারপাড়া | 74 | 1,291 | 1,288 | [PDF](pdf/101915_com_1288_female_without_photo_72_2025-11-24.pdf) | [JSON](json/101915_com_1288_female_without_photo_72_2025-11-24.json) |
| 3 | `101916` | মোনারপটল | 17 | 250 | 249 | [PDF](pdf/101916_com_249_male_without_photo_15_2025-11-24.pdf) | [JSON](json/101916_com_249_male_without_photo_15_2025-11-24.json) |
| 4 | `101916` | মোনারপটল | 18 | 269 | 269 | [PDF](pdf/101916_com_269_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101916_com_269_female_without_photo_16_2025-11-24.json) |
| 5 | `101917` | হলিদাবগা | 60 | 1,028 | 1,016 | [PDF](pdf/101917_com_1016_male_without_photo_58_2025-11-24.pdf) | [JSON](json/101917_com_1016_male_without_photo_58_2025-11-24.json) |
| 6 | `101917` | হলিদাবগা | 61 | 1,046 | 1,041 | [PDF](pdf/101917_com_1041_female_without_photo_59_2025-11-24.pdf) | [JSON](json/101917_com_1041_female_without_photo_59_2025-11-24.json) |
| 7 | `101918` | জোড়গাছা | 60 | 1,035 | 1,031 | [PDF](pdf/101918_com_1031_female_without_photo_58_2025-11-24.pdf) | [JSON](json/101918_com_1031_female_without_photo_58_2025-11-24.json) |
| 8 | `101918` | জোড়গাছা | 58 | 996 | 984 | [PDF](pdf/101918_com_984_male_without_photo_56_2025-11-24.pdf) | [JSON](json/101918_com_984_male_without_photo_56_2025-11-24.json) |
| 9 | `101919` | নিমেরপাড়া | 16 | 239 | 235 | [PDF](pdf/101919_com_235_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101919_com_235_male_without_photo_14_2025-11-24.json) |
| 10 | `101919` | নিমেরপাড়া | 18 | 270 | 270 | [PDF](pdf/101919_com_270_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101919_com_270_female_without_photo_16_2025-11-24.json) |
| 11 | `101920` | ভেলুরপাড়া | 50 | 856 | 849 | [PDF](pdf/101920_com_849_male_without_photo_48_2025-11-24.pdf) | [JSON](json/101920_com_849_male_without_photo_48_2025-11-24.json) |
| 12 | `101920` | ভেলুরপাড়া | 50 | 851 | 850 | [PDF](pdf/101920_com_850_female_without_photo_48_2025-11-24.pdf) | [JSON](json/101920_com_850_female_without_photo_48_2025-11-24.json) |
| 13 | `101921` | গোসাইবাড়ী | 50 | 855 | 854 | [PDF](pdf/101921_com_854_female_without_photo_48_2025-11-24.pdf) | [JSON](json/101921_com_854_female_without_photo_48_2025-11-24.json) |
| 14 | `101921` | গোসাইবাড়ী | 52 | 882 | 881 | [PDF](pdf/101921_com_881_male_without_photo_50_2025-11-24.pdf) | [JSON](json/101921_com_881_male_without_photo_50_2025-11-24.json) |
| 15 | `101922` | চরপাড়া | 62 | 1,067 | 1,059 | [PDF](pdf/101922_com_1059_male_without_photo_60_2025-11-24.pdf) | [JSON](json/101922_com_1059_male_without_photo_60_2025-11-24.json) |
| 16 | `101922` | চরপাড়া | 63 | 1,094 | 1,094 | [PDF](pdf/101922_com_1094_female_without_photo_61_2025-11-24.pdf) | [JSON](json/101922_com_1094_female_without_photo_61_2025-11-24.json) |
| 17 | `101923` | সর্জনপাড়া | 9 | 118 | 118 | [PDF](pdf/101923_com_118_female_without_photo_7_2025-11-24.pdf) | [JSON](json/101923_com_118_female_without_photo_7_2025-11-24.json) |
| 18 | `101923` | সর্জনপাড়া | 9 | 120 | 119 | [PDF](pdf/101923_com_119_male_without_photo_7_2025-11-24.pdf) | [JSON](json/101923_com_119_male_without_photo_7_2025-11-24.json) |
| 19 | `101924` | সোনাকানিয়া | 89 | 1,550 | 1,545 | [PDF](pdf/101924_com_1545_female_without_photo_87_2025-11-24.pdf) | [JSON](json/101924_com_1545_female_without_photo_87_2025-11-24.json) |
| 20 | `101924` | সোনাকানিয়া | 92 | 1,604 | 1,589 | [PDF](pdf/101924_com_1589_male_without_photo_90_2025-11-24.pdf) | [JSON](json/101924_com_1589_male_without_photo_90_2025-11-24.json) |
| 21 | `101925` | মধ্য দিঘলকান্দি | 107 | 1,886 | 1,883 | [PDF](pdf/101925_com_1883_female_without_photo_105_2025-11-24.pdf) | [JSON](json/101925_com_1883_female_without_photo_105_2025-11-24.json) |
| 22 | `101925` | মধ্য দিঘলকান্দি | 109 | 1,912 | 1,901 | [PDF](pdf/101925_com_1901_male_without_photo_107_2025-11-24.pdf) | [JSON](json/101925_com_1901_male_without_photo_107_2025-11-24.json) |
| 23 | `101926` | গনসারপাড়া | 21 | 337 | 333 | [PDF](pdf/101926_com_333_male_without_photo_19_2025-11-24.pdf) | [JSON](json/101926_com_333_male_without_photo_19_2025-11-24.json) |
| 24 | `101926` | গনসারপাড়া | 22 | 353 | 353 | [PDF](pdf/101926_com_353_female_without_photo_20_2025-11-24.pdf) | [JSON](json/101926_com_353_female_without_photo_20_2025-11-24.json) |
| 25 | `101927` | ঠাকুরপাড়া | 39 | 648 | 646 | [PDF](pdf/101927_com_646_male_without_photo_37_2025-11-24.pdf) | [JSON](json/101927_com_646_male_without_photo_37_2025-11-24.json) |
| 26 | `101927` | ঠাকুরপাড়া | 40 | 671 | 670 | [PDF](pdf/101927_com_670_female_without_photo_38_2025-11-24.pdf) | [JSON](json/101927_com_670_female_without_photo_38_2025-11-24.json) |
| 27 | `101928` | পূর্ব করমজা | 20 | 313 | 312 | [PDF](pdf/101928_com_312_female_without_photo_18_2025-11-24.pdf) | [JSON](json/101928_com_312_female_without_photo_18_2025-11-24.json) |
| 28 | `101928` | পূর্ব করমজা | 21 | 325 | 323 | [PDF](pdf/101928_com_323_male_without_photo_19_2025-11-24.pdf) | [JSON](json/101928_com_323_male_without_photo_19_2025-11-24.json) |
| 29 | `101929` | পশ্চিম করমজা | 24 | 387 | 386 | [PDF](pdf/101929_com_386_female_without_photo_22_2025-11-24.pdf) | [JSON](json/101929_com_386_female_without_photo_22_2025-11-24.json) |
| 30 | `101929` | পশ্চিম করমজা | 26 | 423 | 423 | [PDF](pdf/101929_com_423_male_without_photo_24_2025-11-24.pdf) | [JSON](json/101929_com_423_male_without_photo_24_2025-11-24.json) |
| 31 | `101930` | হাট করমজা | 14 | 212 | 212 | [PDF](pdf/101930_com_212_male_without_photo_12_2025-11-24.pdf) | [JSON](json/101930_com_212_male_without_photo_12_2025-11-24.json) |
| 32 | `101930` | হাট করমজা | 15 | 217 | 217 | [PDF](pdf/101930_com_217_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101930_com_217_female_without_photo_13_2025-11-24.json) |
| 33 | `101931` | গ্রাম করমজা | 27 | 439 | 436 | [PDF](pdf/101931_com_436_male_without_photo_25_2025-11-24.pdf) | [JSON](json/101931_com_436_male_without_photo_25_2025-11-24.json) |
| 34 | `101931` | গ্রাম করমজা | 29 | 471 | 468 | [PDF](pdf/101931_com_468_female_without_photo_27_2025-11-24.pdf) | [JSON](json/101931_com_468_female_without_photo_27_2025-11-24.json) |
| 35 | `101932` | উত্তর বয়রা | 72 | 1,241 | 1,238 | [PDF](pdf/101932_com_1238_male_without_photo_70_2025-11-24.pdf) | [JSON](json/101932_com_1238_male_without_photo_70_2025-11-24.json) |
| 36 | `101932` | উত্তর বয়রা | 72 | 1,257 | 1,251 | [PDF](pdf/101932_com_1251_female_without_photo_70_2025-11-24.pdf) | [JSON](json/101932_com_1251_female_without_photo_70_2025-11-24.json) |
| 37 | `101933` | কুশাহাটা | 18 | 283 | 281 | [PDF](pdf/101933_com_281_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101933_com_281_female_without_photo_16_2025-11-24.json) |
| 38 | `101933` | কুশাহাটা | 19 | 289 | 286 | [PDF](pdf/101933_com_286_male_without_photo_17_2025-11-24.pdf) | [JSON](json/101933_com_286_male_without_photo_17_2025-11-24.json) |
| 39 | `101934` | কোড়াডাংগা | 15 | 225 | 224 | [PDF](pdf/101934_com_224_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101934_com_224_female_without_photo_13_2025-11-24.json) |
| 40 | `101934` | কোড়াডাংগা | 16 | 246 | 241 | [PDF](pdf/101934_com_241_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101934_com_241_male_without_photo_14_2025-11-24.json) |
| 41 | `101935` | পোড়াপাইকড় | 25 | 395 | 388 | [PDF](pdf/101935_com_388_female_without_photo_23_2025-11-24.pdf) | [JSON](json/101935_com_388_female_without_photo_23_2025-11-24.json) |
| 42 | `101935` | পোড়াপাইকড় | 26 | 427 | 416 | [PDF](pdf/101935_com_416_male_without_photo_24_2025-11-24.pdf) | [JSON](json/101935_com_416_male_without_photo_24_2025-11-24.json) |
| 43 | `101936` | নওদাবগা | 83 | 1,446 | 1,440 | [PDF](pdf/101936_com_1440_female_without_photo_81_2025-11-24.pdf) | [JSON](json/101936_com_1440_female_without_photo_81_2025-11-24.json) |
| 44 | `101936` | নওদাবগা | 88 | 1,532 | 1,529 | [PDF](pdf/101936_com_1529_male_without_photo_86_2025-11-24.pdf) | [JSON](json/101936_com_1529_male_without_photo_86_2025-11-24.json) |

### Madhupur

13 areas, 26 files, 18,793 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101937` | গারামারা | 37 | 623 | 617 | [PDF](pdf/101937_com_617_male_without_photo_35_2025-11-24.pdf) | [JSON](json/101937_com_617_male_without_photo_35_2025-11-24.json) |
| 2 | `101937` | গারামারা | 38 | 645 | 644 | [PDF](pdf/101937_com_644_female_without_photo_36_2025-11-24.pdf) | [JSON](json/101937_com_644_female_without_photo_36_2025-11-24.json) |
| 3 | `101938` | চর চকনন্দন | 9 | 111 | 111 | [PDF](pdf/101938_com_111_female_without_photo_7_2025-11-24.pdf) | [JSON](json/101938_com_111_female_without_photo_7_2025-11-24.json) |
| 4 | `101938` | চর চকনন্দন | 9 | 122 | 121 | [PDF](pdf/101938_com_121_male_without_photo_7_2025-11-24.pdf) | [JSON](json/101938_com_121_male_without_photo_7_2025-11-24.json) |
| 5 | `101939` | চর মধুপুর | 25 | 396 | 393 | [PDF](pdf/101939_com_393_male_without_photo_23_2025-11-24.pdf) | [JSON](json/101939_com_393_male_without_photo_23_2025-11-24.json) |
| 6 | `101939` | চর মধুপুর | 27 | 430 | 427 | [PDF](pdf/101939_com_427_female_without_photo_25_2025-11-24.pdf) | [JSON](json/101939_com_427_female_without_photo_25_2025-11-24.json) |
| 7 | `101940` | মধুপুর | 53 | 903 | 901 | [PDF](pdf/101940_com_901_female_without_photo_51_2025-11-24.pdf) | [JSON](json/101940_com_901_female_without_photo_51_2025-11-24.json) |
| 8 | `101940` | মধুপুর | 56 | 962 | 957 | [PDF](pdf/101940_com_957_male_without_photo_54_2025-11-24.pdf) | [JSON](json/101940_com_957_male_without_photo_54_2025-11-24.json) |
| 9 | `101941` | দড়িহঁাসরাজ | 55 | 936 | 935 | [PDF](pdf/101941_com_935_female_without_photo_53_2025-11-24.pdf) | [JSON](json/101941_com_935_female_without_photo_53_2025-11-24.json) |
| 10 | `101941` | দড়িহঁাসরাজ | 55 | 941 | 937 | [PDF](pdf/101941_com_937_male_without_photo_53_2025-11-24.pdf) | [JSON](json/101941_com_937_male_without_photo_53_2025-11-24.json) |
| 11 | `101942` | উত্তর কালাইহাটা | 34 | 561 | 560 | [PDF](pdf/101942_com_560_female_without_photo_32_2025-11-24.pdf) | [JSON](json/101942_com_560_female_without_photo_32_2025-11-24.json) |
| 12 | `101942` | উত্তর কালাইহাটা | 38 | 630 | 629 | [PDF](pdf/101942_com_629_male_without_photo_36_2025-11-24.pdf) | [JSON](json/101942_com_629_male_without_photo_36_2025-11-24.json) |
| 13 | `101943` | ফুলবাড়ীয়া | 32 | 520 | 520 | [PDF](pdf/101943_com_520_female_without_photo_30_2025-11-24.pdf) | [JSON](json/101943_com_520_female_without_photo_30_2025-11-24.json) |
| 14 | `101943` | ফুলবাড়ীয়া | 33 | 548 | 547 | [PDF](pdf/101943_com_547_male_without_photo_31_2025-11-24.pdf) | [JSON](json/101943_com_547_male_without_photo_31_2025-11-24.json) |
| 15 | `101944` | ছাতিয়ানতলা | 47 | 798 | 795 | [PDF](pdf/101944_com_795_female_without_photo_45_2025-11-24.pdf) | [JSON](json/101944_com_795_female_without_photo_45_2025-11-24.json) |
| 16 | `101944` | ছাতিয়ানতলা | 52 | 891 | 883 | [PDF](pdf/101944_com_883_male_without_photo_50_2025-11-24.pdf) | [JSON](json/101944_com_883_male_without_photo_50_2025-11-24.json) |
| 17 | `101945` | শালিখা | 85 | 1,489 | 1,487 | [PDF](pdf/101945_com_1487_female_without_photo_83_2025-11-24.pdf) | [JSON](json/101945_com_1487_female_without_photo_83_2025-11-24.json) |
| 18 | `101945` | শালিখা | 86 | 1,494 | 1,488 | [PDF](pdf/101945_com_1488_male_without_photo_84_2025-11-24.pdf) | [JSON](json/101945_com_1488_male_without_photo_84_2025-11-24.json) |
| 19 | `101946` | হঁাসরাজ | 60 | 1,037 | 1,035 | [PDF](pdf/101946_com_1035_female_without_photo_58_2025-11-24.pdf) | [JSON](json/101946_com_1035_female_without_photo_58_2025-11-24.json) |
| 20 | `101946` | হঁাসরাজ | 64 | 1,096 | 1,088 | [PDF](pdf/101946_com_1088_male_without_photo_62_2025-11-24.pdf) | [JSON](json/101946_com_1088_male_without_photo_62_2025-11-24.json) |
| 21 | `101947` | উত্তর গজারিয়া | 18 | 278 | 278 | [PDF](pdf/101947_com_278_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101947_com_278_female_without_photo_16_2025-11-24.json) |
| 22 | `101947` | উত্তর গজারিয়া | 20 | 304 | 303 | [PDF](pdf/101947_com_303_male_without_photo_18_2025-11-24.pdf) | [JSON](json/101947_com_303_male_without_photo_18_2025-11-24.json) |
| 23 | `101948` | পশ্চিম তেকানী (উত্তর) | 46 | 786 | 785 | [PDF](pdf/101948_com_785_female_without_photo_44_2025-11-24.pdf) | [JSON](json/101948_com_785_female_without_photo_44_2025-11-24.json) |
| 24 | `101948` | পশ্চিম তেকানী (উত্তর) | 47 | 799 | 796 | [PDF](pdf/101948_com_796_male_without_photo_45_2025-11-24.pdf) | [JSON](json/101948_com_796_male_without_photo_45_2025-11-24.json) |
| 25 | `101949` | পশ্চিম তেকানী (দক্ষিণ) | 42 | 711 | 711 | [PDF](pdf/101949_com_711_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101949_com_711_male_without_photo_40_2025-11-24.json) |
| 26 | `101949` | পশ্চিম তেকানী (দক্ষিণ) | 46 | 782 | 781 | [PDF](pdf/101949_com_781_female_without_photo_44_2025-11-24.pdf) | [JSON](json/101949_com_781_female_without_photo_44_2025-11-24.json) |

### Pakulla

16 areas, 32 files, 21,970 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101964` | উত্তর করমজা | 49 | 831 | 829 | [PDF](pdf/101964_com_829_female_without_photo_47_2025-11-24.pdf) | [JSON](json/101964_com_829_female_without_photo_47_2025-11-24.json) |
| 2 | `101964` | উত্তর করমজা | 50 | 854 | 853 | [PDF](pdf/101964_com_853_male_without_photo_48_2025-11-24.pdf) | [JSON](json/101964_com_853_male_without_photo_48_2025-11-24.json) |
| 3 | `101965` | সাতবেকী | 38 | 628 | 626 | [PDF](pdf/101965_com_626_female_without_photo_36_2025-11-24.pdf) | [JSON](json/101965_com_626_female_without_photo_36_2025-11-24.json) |
| 4 | `101965` | সাতবেকী | 39 | 653 | 650 | [PDF](pdf/101965_com_650_male_without_photo_37_2025-11-24.pdf) | [JSON](json/101965_com_650_male_without_photo_37_2025-11-24.json) |
| 5 | `101966` | নিশ্চিন্তপুর | 37 | 614 | 613 | [PDF](pdf/101966_com_613_female_without_photo_35_2025-11-24.pdf) | [JSON](json/101966_com_613_female_without_photo_35_2025-11-24.json) |
| 6 | `101966` | নিশ্চিন্তপুর | 38 | 635 | 624 | [PDF](pdf/101966_com_624_male_without_photo_36_2025-11-24.pdf) | [JSON](json/101966_com_624_male_without_photo_36_2025-11-24.json) |
| 7 | `101967` | পদ্মপাড়া | 48 | 816 | 810 | [PDF](pdf/101967_com_810_male_without_photo_46_2025-11-24.pdf) | [JSON](json/101967_com_810_male_without_photo_46_2025-11-24.json) |
| 8 | `101967` | পদ্মপাড়া | 48 | 818 | 816 | [PDF](pdf/101967_com_816_female_without_photo_46_2025-11-24.pdf) | [JSON](json/101967_com_816_female_without_photo_46_2025-11-24.json) |
| 9 | `101968` | পাকুল্লা (৩ নং ওয়ার্ড) | 5 | 45 | 45 | [PDF](pdf/101968_com_45_female_without_photo_3_2025-11-24.pdf) | [JSON](json/101968_com_45_female_without_photo_3_2025-11-24.json) |
| 10 | `101968` | পাকুল্লা (৩ নং ওয়ার্ড) | 5 | 46 | 46 | [PDF](pdf/101968_com_46_male_without_photo_3_2025-11-24.pdf) | [JSON](json/101968_com_46_male_without_photo_3_2025-11-24.json) |
| 11 | `101969` | মিলনেরপাড়া | 43 | 735 | 732 | [PDF](pdf/101969_com_732_female_without_photo_41_2025-11-24.pdf) | [JSON](json/101969_com_732_female_without_photo_41_2025-11-24.json) |
| 12 | `101969` | মিলনেরপাড়া | 47 | 793 | 790 | [PDF](pdf/101969_com_790_male_without_photo_45_2025-11-24.pdf) | [JSON](json/101969_com_790_male_without_photo_45_2025-11-24.json) |
| 13 | `101970` | খাটিয়ামারী | 11 | 150 | 148 | [PDF](pdf/101970_com_148_male_without_photo_9_2025-11-24.pdf) | [JSON](json/101970_com_148_male_without_photo_9_2025-11-24.json) |
| 14 | `101970` | খাটিয়ামারী | 13 | 183 | 183 | [PDF](pdf/101970_com_183_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101970_com_183_female_without_photo_11_2025-11-24.json) |
| 15 | `101971` | পাকুল্লা (৪ নং ওয়ার্ড) | 29 | 466 | 466 | [PDF](pdf/101971_com_466_female_without_photo_27_2025-11-24.pdf) | [JSON](json/101971_com_466_female_without_photo_27_2025-11-24.json) |
| 16 | `101971` | পাকুল্লা (৪ নং ওয়ার্ড) | 30 | 484 | 481 | [PDF](pdf/101971_com_481_male_without_photo_28_2025-11-24.pdf) | [JSON](json/101971_com_481_male_without_photo_28_2025-11-24.json) |
| 17 | `101972` | রাধাকান্তপুর | 38 | 630 | 627 | [PDF](pdf/101972_com_627_female_without_photo_36_2025-11-24.pdf) | [JSON](json/101972_com_627_female_without_photo_36_2025-11-24.json) |
| 18 | `101972` | রাধাকান্তপুর | 42 | 705 | 703 | [PDF](pdf/101972_com_703_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101972_com_703_male_without_photo_40_2025-11-24.json) |
| 19 | `101973` | পাকুল্লা (৫ নং ওয়ার্ড) | 92 | 1,612 | 1,604 | [PDF](pdf/101973_com_1604_male_without_photo_90_2025-11-24.pdf) | [JSON](json/101973_com_1604_male_without_photo_90_2025-11-24.json) |
| 20 | `101973` | পাকুল্লা (৫ নং ওয়ার্ড) | 94 | 1,650 | 1,647 | [PDF](pdf/101973_com_1647_female_without_photo_92_2025-11-24.pdf) | [JSON](json/101973_com_1647_female_without_photo_92_2025-11-24.json) |
| 21 | `101974` | হুয়াকুয়া | 88 | 1,529 | 1,524 | [PDF](pdf/101974_com_1524_female_without_photo_86_2025-11-24.pdf) | [JSON](json/101974_com_1524_female_without_photo_86_2025-11-24.json) |
| 22 | `101974` | হুয়াকুয়া | 90 | 1,569 | 1,565 | [PDF](pdf/101974_com_1565_male_without_photo_88_2025-11-24.pdf) | [JSON](json/101974_com_1565_male_without_photo_88_2025-11-24.json) |
| 23 | `101975` | আচারেরপাড়া | 28 | 453 | 452 | [PDF](pdf/101975_com_452_female_without_photo_26_2025-11-24.pdf) | [JSON](json/101975_com_452_female_without_photo_26_2025-11-24.json) |
| 24 | `101975` | আচারেরপাড়া | 29 | 467 | 461 | [PDF](pdf/101975_com_461_male_without_photo_27_2025-11-24.pdf) | [JSON](json/101975_com_461_male_without_photo_27_2025-11-24.json) |
| 25 | `101976` | শাহবাজপুর | 25 | 404 | 404 | [PDF](pdf/101976_com_404_female_without_photo_23_2025-11-24.pdf) | [JSON](json/101976_com_404_female_without_photo_23_2025-11-24.json) |
| 26 | `101976` | শাহবাজপুর | 27 | 446 | 440 | [PDF](pdf/101976_com_440_male_without_photo_25_2025-11-24.pdf) | [JSON](json/101976_com_440_male_without_photo_25_2025-11-24.json) |
| 27 | `101977` | শ্যামপুর | 59 | 1,014 | 1,011 | [PDF](pdf/101977_com_1011_female_without_photo_57_2025-11-24.pdf) | [JSON](json/101977_com_1011_female_without_photo_57_2025-11-24.json) |
| 28 | `101977` | শ্যামপুর | 64 | 1,104 | 1,093 | [PDF](pdf/101977_com_1093_male_without_photo_62_2025-11-24.pdf) | [JSON](json/101977_com_1093_male_without_photo_62_2025-11-24.json) |
| 29 | `101978` | পূর্ব সুজাইতপুর | 36 | 607 | 606 | [PDF](pdf/101978_com_606_female_without_photo_34_2025-11-24.pdf) | [JSON](json/101978_com_606_female_without_photo_34_2025-11-24.json) |
| 30 | `101978` | পূর্ব সুজাইতপুর | 38 | 645 | 636 | [PDF](pdf/101978_com_636_male_without_photo_36_2025-11-24.pdf) | [JSON](json/101978_com_636_male_without_photo_36_2025-11-24.json) |
| 31 | `101979` | বালুয়াপাড়া | 13 | 183 | 183 | [PDF](pdf/101979_com_183_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101979_com_183_female_without_photo_11_2025-11-24.json) |
| 32 | `101979` | বালুয়াপাড়া | 14 | 201 | 200 | [PDF](pdf/101979_com_200_male_without_photo_12_2025-11-24.pdf) | [JSON](json/101979_com_200_male_without_photo_12_2025-11-24.json) |

### Sonatola

19 areas, 38 files, 14,449 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101847` | সুজাইতপুর (পূর্বপাড়া, আগপাড়া ও মধ্যপাড়া) | 45 | 762 | 759 | [PDF](pdf/101847_com_759_female_without_photo_43_2025-11-24.pdf) | [JSON](json/101847_com_759_female_without_photo_43_2025-11-24.json) |
| 2 | `101847` | সুজাইতপুর (পূর্বপাড়া, আগপাড়া ও মধ্যপাড়া) | 46 | 776 | 771 | [PDF](pdf/101847_com_771_male_without_photo_44_2025-11-24.pdf) | [JSON](json/101847_com_771_male_without_photo_44_2025-11-24.json) |
| 3 | `101848` | সুজাইতপুর (পশ্চিমপাড়া, নয়াপাড়া ও বরশীপাড়া) | 45 | 759 | 756 | [PDF](pdf/101848_com_756_female_without_photo_43_2025-11-24.pdf) | [JSON](json/101848_com_756_female_without_photo_43_2025-11-24.json) |
| 4 | `101848` | সুজাইতপুর (পশ্চিমপাড়া, নয়াপাড়া ও বরশীপাড়া) | 45 | 771 | 765 | [PDF](pdf/101848_com_765_male_without_photo_43_2025-11-24.pdf) | [JSON](json/101848_com_765_male_without_photo_43_2025-11-24.json) |
| 5 | `101849` | চামুরপাড়া | 35 | 586 | 584 | [PDF](pdf/101849_com_584_female_without_photo_33_2025-11-24.pdf) | [JSON](json/101849_com_584_female_without_photo_33_2025-11-24.json) |
| 6 | `101849` | চামুরপাড়া | 36 | 602 | 597 | [PDF](pdf/101849_com_597_male_without_photo_34_2025-11-24.pdf) | [JSON](json/101849_com_597_male_without_photo_34_2025-11-24.json) |
| 7 | `101850` | রংরারপাড়া | 23 | 360 | 353 | [PDF](pdf/101850_com_353_male_without_photo_21_2025-11-24.pdf) | [JSON](json/101850_com_353_male_without_photo_21_2025-11-24.json) |
| 8 | `101850` | রংরারপাড়া | 23 | 370 | 368 | [PDF](pdf/101850_com_368_female_without_photo_21_2025-11-24.pdf) | [JSON](json/101850_com_368_female_without_photo_21_2025-11-24.json) |
| 9 | `101851` | খিতাবেরপাড়া | 12 | 175 | 175 | [PDF](pdf/101851_com_175_female_without_photo_10_2025-11-24.pdf) | [JSON](json/101851_com_175_female_without_photo_10_2025-11-24.json) |
| 10 | `101851` | খিতাবেরপাড়া | 13 | 190 | 190 | [PDF](pdf/101851_com_190_male_without_photo_11_2025-11-24.pdf) | [JSON](json/101851_com_190_male_without_photo_11_2025-11-24.json) |
| 11 | `101852` | রানীরপাড়া (নয়াপাড়া, খোদাদিলপাড়া, কেল্লাগাড়ী) | 26 | 419 | 416 | [PDF](pdf/101852_com_416_male_without_photo_24_2025-11-24.pdf) | [JSON](json/101852_com_416_male_without_photo_24_2025-11-24.json) |
| 12 | `101852` | রানীরপাড়া (নয়াপাড়া, খোদাদিলপাড়া, কেল্লাগাড়ী) | 26 | 422 | 422 | [PDF](pdf/101852_com_422_female_without_photo_24_2025-11-24.pdf) | [JSON](json/101852_com_422_female_without_photo_24_2025-11-24.json) |
| 13 | `101853` | রাণীরপাড়া (৫ নং ওয়ার্ড) | 46 | 778 | 772 | [PDF](pdf/101853_com_772_male_without_photo_44_2025-11-24.pdf) | [JSON](json/101853_com_772_male_without_photo_44_2025-11-24.json) |
| 14 | `101853` | রাণীরপাড়া (৫ নং ওয়ার্ড) | 46 | 774 | 774 | [PDF](pdf/101853_com_774_female_without_photo_44_2025-11-24.pdf) | [JSON](json/101853_com_774_female_without_photo_44_2025-11-24.json) |
| 15 | `101854` | নামাজখালী | 50 | 848 | 847 | [PDF](pdf/101854_com_847_female_without_photo_48_2025-11-24.pdf) | [JSON](json/101854_com_847_female_without_photo_48_2025-11-24.json) |
| 16 | `101854` | নামাজখালী | 51 | 876 | 872 | [PDF](pdf/101854_com_872_male_without_photo_49_2025-11-24.pdf) | [JSON](json/101854_com_872_male_without_photo_49_2025-11-24.json) |
| 17 | `101855` | কাবিলপুর (৭ নং ওয়ার্ড) | 35 | 583 | 579 | [PDF](pdf/101855_com_579_male_without_photo_33_2025-11-24.pdf) | [JSON](json/101855_com_579_male_without_photo_33_2025-11-24.json) |
| 18 | `101855` | কাবিলপুর (৭ নং ওয়ার্ড) | 36 | 608 | 606 | [PDF](pdf/101855_com_606_female_without_photo_34_2025-11-24.pdf) | [JSON](json/101855_com_606_female_without_photo_34_2025-11-24.json) |
| 19 | `101856` | রাণীরপাড়া (৭ নং ওয়ার্ড) | 3 | 3 | 3 | [PDF](pdf/101856_com_3_female_without_photo_1_2025-11-24.pdf) | [JSON](json/101856_com_3_female_without_photo_1_2025-11-24.json) |
| 20 | `101856` | রাণীরপাড়া (৭ নং ওয়ার্ড) | 3 | 9 | 9 | [PDF](pdf/101856_com_9_male_without_photo_1_2025-11-24.pdf) | [JSON](json/101856_com_9_male_without_photo_1_2025-11-24.json) |
| 21 | `101857` | বালুরপাড়া | 12 | 170 | 170 | [PDF](pdf/101857_com_170_male_without_photo_10_2025-11-24.pdf) | [JSON](json/101857_com_170_male_without_photo_10_2025-11-24.json) |
| 22 | `101857` | বালুরপাড়া | 13 | 181 | 181 | [PDF](pdf/101857_com_181_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101857_com_181_female_without_photo_11_2025-11-24.json) |
| 23 | `101858` | গড়চৈতন্যপুর(৭ নং ওয়ার্ড) | 6 | 57 | 57 | [PDF](pdf/101858_com_57_female_without_photo_4_2025-11-24.pdf) | [JSON](json/101858_com_57_female_without_photo_4_2025-11-24.json) |
| 24 | `101858` | গড়চৈতন্যপুর(৭ নং ওয়ার্ড) | 6 | 65 | 64 | [PDF](pdf/101858_com_64_male_without_photo_4_2025-11-24.pdf) | [JSON](json/101858_com_64_male_without_photo_4_2025-11-24.json) |
| 25 | `101859` | চকনন্দন (৮ নং ওয়ার্ড) | 36 | 602 | 602 | [PDF](pdf/101859_com_602_female_without_photo_34_2025-11-24.pdf) | [JSON](json/101859_com_602_female_without_photo_34_2025-11-24.json) |
| 26 | `101859` | চকনন্দন (৮ নং ওয়ার্ড) | 37 | 620 | 614 | [PDF](pdf/101859_com_614_male_without_photo_35_2025-11-24.pdf) | [JSON](json/101859_com_614_male_without_photo_35_2025-11-24.json) |
| 27 | `101860` | কামালেরপাড়া | 11 | 159 | 159 | [PDF](pdf/101860_com_159_female_without_photo_9_2025-11-24.pdf) | [JSON](json/101860_com_159_female_without_photo_9_2025-11-24.json) |
| 28 | `101860` | কামালেরপাড়া | 12 | 167 | 166 | [PDF](pdf/101860_com_166_male_without_photo_10_2025-11-24.pdf) | [JSON](json/101860_com_166_male_without_photo_10_2025-11-24.json) |
| 29 | `101861` | বিশ্বনাথপুর (৮ নং ওয়ার্ড) | 4 | 33 | 32 | [PDF](pdf/101861_com_32_male_without_photo_2_2025-11-24.pdf) | [JSON](json/101861_com_32_male_without_photo_2_2025-11-24.json) |
| 30 | `101861` | বিশ্বনাথপুর (৮ নং ওয়ার্ড) | 5 | 36 | 36 | [PDF](pdf/101861_com_36_female_without_photo_3_2025-11-24.pdf) | [JSON](json/101861_com_36_female_without_photo_3_2025-11-24.json) |
| 31 | `101862` | চকনন্দন (৯ নং ওয়ার্ড) | 4 | 31 | 31 | [PDF](pdf/101862_com_31_male_without_photo_2_2025-11-24.pdf) | [JSON](json/101862_com_31_male_without_photo_2_2025-11-24.json) |
| 32 | `101862` | চকনন্দন (৯ নং ওয়ার্ড) | 4 | 33 | 33 | [PDF](pdf/101862_com_33_female_without_photo_2_2025-11-24.pdf) | [JSON](json/101862_com_33_female_without_photo_2_2025-11-24.json) |
| 33 | `101863` | মন্ডমালা (নয়াপাড়া) | 23 | 370 | 367 | [PDF](pdf/101863_com_367_male_without_photo_21_2025-11-24.pdf) | [JSON](json/101863_com_367_male_without_photo_21_2025-11-24.json) |
| 34 | `101863` | মন্ডমালা (নয়াপাড়া) | 23 | 369 | 369 | [PDF](pdf/101863_com_369_female_without_photo_21_2025-11-24.pdf) | [JSON](json/101863_com_369_female_without_photo_21_2025-11-24.json) |
| 35 | `101864` | বিশ্বনাথপুর (চরবিশ্বনাথপুর, গুচ্ছগ্রাম) | 21 | 326 | 326 | [PDF](pdf/101864_com_326_female_without_photo_19_2025-11-24.pdf) | [JSON](json/101864_com_326_female_without_photo_19_2025-11-24.json) |
| 36 | `101864` | বিশ্বনাথপুর (চরবিশ্বনাথপুর, গুচ্ছগ্রাম) | 25 | 394 | 392 | [PDF](pdf/101864_com_392_male_without_photo_23_2025-11-24.pdf) | [JSON](json/101864_com_392_male_without_photo_23_2025-11-24.json) |
| 37 | `101865` | গড়চৈতন্যপুর(৯ নং ওয়ার্ড) | 7 | 79 | 78 | [PDF](pdf/101865_com_78_male_without_photo_5_2025-11-24.pdf) | [JSON](json/101865_com_78_male_without_photo_5_2025-11-24.json) |
| 38 | `101865` | গড়চৈতন্যপুর(৯ নং ওয়ার্ড) | 7 | 86 | 86 | [PDF](pdf/101865_com_86_female_without_photo_5_2025-11-24.pdf) | [JSON](json/101865_com_86_female_without_photo_5_2025-11-24.json) |

### Tekani Chukaina

14 areas, 28 files, 14,103 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101950` | পূর্ব তেকানী (১ নং ওয়ার্ড) | 53 | 908 | 901 | [PDF](pdf/101950_com_901_male_without_photo_51_2025-11-24.pdf) | [JSON](json/101950_com_901_male_without_photo_51_2025-11-24.json) |
| 2 | `101950` | পূর্ব তেকানী (১ নং ওয়ার্ড) | 53 | 903 | 902 | [PDF](pdf/101950_com_902_female_without_photo_51_2025-11-24.pdf) | [JSON](json/101950_com_902_female_without_photo_51_2025-11-24.json) |
| 3 | `101951` | পূর্ব তেকানী (২ নং ওয়ার্ড) | 52 | 884 | 878 | [PDF](pdf/101951_com_878_male_without_photo_50_2025-11-24.pdf) | [JSON](json/101951_com_878_male_without_photo_50_2025-11-24.json) |
| 4 | `101951` | পূর্ব তেকানী (২ নং ওয়ার্ড) | 52 | 881 | 880 | [PDF](pdf/101951_com_880_female_without_photo_50_2025-11-24.pdf) | [JSON](json/101951_com_880_female_without_photo_50_2025-11-24.json) |
| 5 | `101952` | পূর্ব তেকানী (৩ নং ওয়ার্ড) | 35 | 576 | 573 | [PDF](pdf/101952_com_573_male_without_photo_33_2025-11-24.pdf) | [JSON](json/101952_com_573_male_without_photo_33_2025-11-24.json) |
| 6 | `101952` | পূর্ব তেকানী (৩ নং ওয়ার্ড) | 35 | 580 | 580 | [PDF](pdf/101952_com_580_female_without_photo_33_2025-11-24.pdf) | [JSON](json/101952_com_580_female_without_photo_33_2025-11-24.json) |
| 7 | `101953` | চর সরলিয়া | 12 | 174 | 174 | [PDF](pdf/101953_com_174_female_without_photo_10_2025-11-24.pdf) | [JSON](json/101953_com_174_female_without_photo_10_2025-11-24.json) |
| 8 | `101953` | চর সরলিয়া | 13 | 185 | 185 | [PDF](pdf/101953_com_185_male_without_photo_11_2025-11-24.pdf) | [JSON](json/101953_com_185_male_without_photo_11_2025-11-24.json) |
| 9 | `101954` | পূর্ব তেকানী (৪ নং ওয়ার্ড) | 42 | 715 | 704 | [PDF](pdf/101954_com_704_male_without_photo_40_2025-11-24.pdf) | [JSON](json/101954_com_704_male_without_photo_40_2025-11-24.json) |
| 10 | `101954` | পূর্ব তেকানী (৪ নং ওয়ার্ড) | 42 | 710 | 710 | [PDF](pdf/101954_com_710_female_without_photo_40_2025-11-24.pdf) | [JSON](json/101954_com_710_female_without_photo_40_2025-11-24.json) |
| 11 | `101955` | চুকাইনগর | 13 | 186 | 184 | [PDF](pdf/101955_com_184_male_without_photo_11_2025-11-24.pdf) | [JSON](json/101955_com_184_male_without_photo_11_2025-11-24.json) |
| 12 | `101955` | চুকাইনগর | 14 | 213 | 212 | [PDF](pdf/101955_com_212_female_without_photo_12_2025-11-24.pdf) | [JSON](json/101955_com_212_female_without_photo_12_2025-11-24.json) |
| 13 | `101956` | ভিখনেরপাড়া | 22 | 346 | 345 | [PDF](pdf/101956_com_345_female_without_photo_20_2025-11-24.pdf) | [JSON](json/101956_com_345_female_without_photo_20_2025-11-24.json) |
| 14 | `101956` | ভিখনেরপাড়া | 23 | 358 | 356 | [PDF](pdf/101956_com_356_male_without_photo_21_2025-11-24.pdf) | [JSON](json/101956_com_356_male_without_photo_21_2025-11-24.json) |
| 15 | `101957` | মহেষপাড়া (৬ নং ওয়ার্ড) | 47 | 794 | 788 | [PDF](pdf/101957_com_788_male_without_photo_45_2025-11-24.pdf) | [JSON](json/101957_com_788_male_without_photo_45_2025-11-24.json) |
| 16 | `101957` | মহেষপাড়া (৬ নং ওয়ার্ড) | 47 | 801 | 801 | [PDF](pdf/101957_com_801_female_without_photo_45_2025-11-24.pdf) | [JSON](json/101957_com_801_female_without_photo_45_2025-11-24.json) |
| 17 | `101958` | মহেষপাড়া (৭ নং ওয়ার্ড) | 35 | 587 | 585 | [PDF](pdf/101958_com_585_male_without_photo_33_2025-11-24.pdf) | [JSON](json/101958_com_585_male_without_photo_33_2025-11-24.json) |
| 18 | `101958` | মহেষপাড়া (৭ নং ওয়ার্ড) | 36 | 594 | 593 | [PDF](pdf/101958_com_593_female_without_photo_34_2025-11-24.pdf) | [JSON](json/101958_com_593_female_without_photo_34_2025-11-24.json) |
| 19 | `101959` | খাবুলিয়া | 23 | 370 | 367 | [PDF](pdf/101959_com_367_female_without_photo_21_2025-11-24.pdf) | [JSON](json/101959_com_367_female_without_photo_21_2025-11-24.json) |
| 20 | `101959` | খাবুলিয়া | 25 | 394 | 393 | [PDF](pdf/101959_com_393_male_without_photo_23_2025-11-24.pdf) | [JSON](json/101959_com_393_male_without_photo_23_2025-11-24.json) |
| 21 | `101960` | মহব্বতেরপাড়া | 27 | 446 | 446 | [PDF](pdf/101960_com_446_male_without_photo_25_2025-11-24.pdf) | [JSON](json/101960_com_446_male_without_photo_25_2025-11-24.json) |
| 22 | `101960` | মহব্বতেরপাড়া | 28 | 454 | 452 | [PDF](pdf/101960_com_452_female_without_photo_26_2025-11-24.pdf) | [JSON](json/101960_com_452_female_without_photo_26_2025-11-24.json) |
| 23 | `101961` | জন্তিয়ারপাড়া | 12 | 173 | 170 | [PDF](pdf/101961_com_170_male_without_photo_10_2025-11-24.pdf) | [JSON](json/101961_com_170_male_without_photo_10_2025-11-24.json) |
| 24 | `101961` | জন্তিয়ারপাড়া | 13 | 187 | 187 | [PDF](pdf/101961_com_187_female_without_photo_11_2025-11-24.pdf) | [JSON](json/101961_com_187_female_without_photo_11_2025-11-24.json) |
| 25 | `101962` | দাউদেরপাড়া | 15 | 231 | 231 | [PDF](pdf/101962_com_231_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101962_com_231_female_without_photo_13_2025-11-24.json) |
| 26 | `101962` | দাউদেরপাড়া | 16 | 248 | 246 | [PDF](pdf/101962_com_246_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101962_com_246_male_without_photo_14_2025-11-24.json) |
| 27 | `101963` | বালিয়াডাংগা | 35 | 587 | 587 | [PDF](pdf/101963_com_587_female_without_photo_33_2025-11-24.pdf) | [JSON](json/101963_com_587_female_without_photo_33_2025-11-24.json) |
| 28 | `101963` | বালিয়াডাংগা | 37 | 618 | 609 | [PDF](pdf/101963_com_609_male_without_photo_35_2025-11-24.pdf) | [JSON](json/101963_com_609_male_without_photo_35_2025-11-24.json) |

### Sonatola Pourashava (সোনাতলা পৌরসভা)

21 areas, 42 files, 21,661 records.

| # | Area | Area name | Pages | Records | Active | PDF | JSON |
|---:|---|---|---:|---:|---:|---|---|
| 1 | `101826` | সোনাতলা পুরাতন বন্দর | 15 | 214 | 210 | [PDF](pdf/101826_com_210_male_without_photo_13_2025-11-24.pdf) | [JSON](json/101826_com_210_male_without_photo_13_2025-11-24.json) |
| 2 | `101826` | সোনাতলা পুরাতন বন্দর | 18 | 275 | 272 | [PDF](pdf/101826_com_272_female_without_photo_16_2025-11-24.pdf) | [JSON](json/101826_com_272_female_without_photo_16_2025-11-24.json) |
| 3 | `101827` | সোনাতলা নতুন বন্দর | 31 | 508 | 506 | [PDF](pdf/101827_com_506_female_without_photo_29_2025-11-24.pdf) | [JSON](json/101827_com_506_female_without_photo_29_2025-11-24.json) |
| 4 | `101827` | সোনাতলা নতুন বন্দর | 32 | 520 | 518 | [PDF](pdf/101827_com_518_male_without_photo_30_2025-11-24.pdf) | [JSON](json/101827_com_518_male_without_photo_30_2025-11-24.json) |
| 5 | `101828` | কাবিলপুর (২ নং ওয়ার্ড) | 13 | 192 | 191 | [PDF](pdf/101828_com_191_male_without_photo_11_2025-11-24.pdf) | [JSON](json/101828_com_191_male_without_photo_11_2025-11-24.json) |
| 6 | `101828` | কাবিলপুর (২ নং ওয়ার্ড) | 14 | 204 | 204 | [PDF](pdf/101828_com_204_female_without_photo_12_2025-11-24.pdf) | [JSON](json/101828_com_204_female_without_photo_12_2025-11-24.json) |
| 7 | `101829` | গড় চৈতন্যপুর (২ নং ওয়ার্ড) | 33 | 549 | 545 | [PDF](pdf/101829_com_545_male_without_photo_31_2025-11-24.pdf) | [JSON](json/101829_com_545_male_without_photo_31_2025-11-24.json) |
| 8 | `101829` | গড় চৈতন্যপুর (২ নং ওয়ার্ড) | 35 | 584 | 583 | [PDF](pdf/101829_com_583_female_without_photo_33_2025-11-24.pdf) | [JSON](json/101829_com_583_female_without_photo_33_2025-11-24.json) |
| 9 | `101830` | গড় ফতেপুর (২ নং ওয়ার্ড) | 22 | 357 | 355 | [PDF](pdf/101830_com_355_male_without_photo_20_2025-11-24.pdf) | [JSON](json/101830_com_355_male_without_photo_20_2025-11-24.json) |
| 10 | `101830` | গড় ফতেপুর (২ নং ওয়ার্ড) | 27 | 439 | 439 | [PDF](pdf/101830_com_439_female_without_photo_25_2025-11-24.pdf) | [JSON](json/101830_com_439_female_without_photo_25_2025-11-24.json) |
| 11 | `101831` | মাগুড়া দহপাড়া | 5 | 51 | 51 | [PDF](pdf/101831_com_51_female_without_photo_3_2025-11-24.pdf) | [JSON](json/101831_com_51_female_without_photo_3_2025-11-24.json) |
| 12 | `101831` | মাগুড়া দহপাড়া | 6 | 57 | 57 | [PDF](pdf/101831_com_57_male_without_photo_4_2025-11-24.pdf) | [JSON](json/101831_com_57_male_without_photo_4_2025-11-24.json) |
| 13 | `101832` | গড় ফতেপুর (৩ নং ওয়ার্ড) | 60 | 1,037 | 1,035 | [PDF](pdf/101832_com_1035_female_without_photo_58_2025-11-24.pdf) | [JSON](json/101832_com_1035_female_without_photo_58_2025-11-24.json) |
| 14 | `101832` | গড় ফতেপুর (৩ নং ওয়ার্ড) | 56 | 954 | 950 | [PDF](pdf/101832_com_950_male_without_photo_54_2025-11-24.pdf) | [JSON](json/101832_com_950_male_without_photo_54_2025-11-24.json) |
| 15 | `101833` | গোপাইবাড়ী | 25 | 411 | 411 | [PDF](pdf/101833_com_411_female_without_photo_23_2025-11-24.pdf) | [JSON](json/101833_com_411_female_without_photo_23_2025-11-24.json) |
| 16 | `101833` | গোপাইবাড়ী | 26 | 419 | 418 | [PDF](pdf/101833_com_418_male_without_photo_24_2025-11-24.pdf) | [JSON](json/101833_com_418_male_without_photo_24_2025-11-24.json) |
| 17 | `101834` | শাহবাজপুর | 30 | 501 | 499 | [PDF](pdf/101834_com_499_male_without_photo_28_2025-11-24.pdf) | [JSON](json/101834_com_499_male_without_photo_28_2025-11-24.json) |
| 18 | `101834` | শাহবাজপুর | 35 | 579 | 578 | [PDF](pdf/101834_com_578_female_without_photo_33_2025-11-24.pdf) | [JSON](json/101834_com_578_female_without_photo_33_2025-11-24.json) |
| 19 | `101835` | বিশুরপাড়া | 26 | 422 | 420 | [PDF](pdf/101835_com_420_male_without_photo_24_2025-11-24.pdf) | [JSON](json/101835_com_420_male_without_photo_24_2025-11-24.json) |
| 20 | `101835` | বিশুরপাড়া | 27 | 434 | 433 | [PDF](pdf/101835_com_433_female_without_photo_25_2025-11-24.pdf) | [JSON](json/101835_com_433_female_without_photo_25_2025-11-24.json) |
| 21 | `101836` | চমরগাছা | 39 | 646 | 645 | [PDF](pdf/101836_com_645_male_without_photo_37_2025-11-24.pdf) | [JSON](json/101836_com_645_male_without_photo_37_2025-11-24.json) |
| 22 | `101836` | চমরগাছা | 42 | 707 | 704 | [PDF](pdf/101836_com_704_female_without_photo_40_2025-11-24.pdf) | [JSON](json/101836_com_704_female_without_photo_40_2025-11-24.json) |
| 23 | `101837` | লাহিড়ীপাড়া | 14 | 203 | 199 | [PDF](pdf/101837_com_199_male_without_photo_12_2025-11-24.pdf) | [JSON](json/101837_com_199_male_without_photo_12_2025-11-24.json) |
| 24 | `101837` | লাহিড়ীপাড়া | 15 | 217 | 216 | [PDF](pdf/101837_com_216_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101837_com_216_female_without_photo_13_2025-11-24.json) |
| 25 | `101838` | ছয়ঘরিয়াপাড়া | 15 | 228 | 228 | [PDF](pdf/101838_com_228_male_without_photo_13_2025-11-24.pdf) | [JSON](json/101838_com_228_male_without_photo_13_2025-11-24.json) |
| 26 | `101838` | ছয়ঘরিয়াপাড়া | 16 | 241 | 239 | [PDF](pdf/101838_com_239_female_without_photo_14_2025-11-24.pdf) | [JSON](json/101838_com_239_female_without_photo_14_2025-11-24.json) |
| 27 | `101839` | নিত্যানন্দনপুর | 15 | 228 | 228 | [PDF](pdf/101839_com_228_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101839_com_228_female_without_photo_13_2025-11-24.json) |
| 28 | `101839` | নিত্যানন্দনপুর | 16 | 233 | 232 | [PDF](pdf/101839_com_232_male_without_photo_14_2025-11-24.pdf) | [JSON](json/101839_com_232_male_without_photo_14_2025-11-24.json) |
| 29 | `101840` | কলাগাছিপাড়া | 10 | 130 | 128 | [PDF](pdf/101840_com_128_male_without_photo_8_2025-11-24.pdf) | [JSON](json/101840_com_128_male_without_photo_8_2025-11-24.json) |
| 30 | `101840` | কলাগাছিপাড়া | 11 | 148 | 148 | [PDF](pdf/101840_com_148_female_without_photo_9_2025-11-24.pdf) | [JSON](json/101840_com_148_female_without_photo_9_2025-11-24.json) |
| 31 | `101841` | খানপাড়া | 24 | 385 | 381 | [PDF](pdf/101841_com_381_male_without_photo_22_2025-11-24.pdf) | [JSON](json/101841_com_381_male_without_photo_22_2025-11-24.json) |
| 32 | `101841` | খানপাড়া | 26 | 418 | 414 | [PDF](pdf/101841_com_414_female_without_photo_24_2025-11-24.pdf) | [JSON](json/101841_com_414_female_without_photo_24_2025-11-24.json) |
| 33 | `101842` | আগুনিয়াতাইড় (উত্তর) | 45 | 768 | 762 | [PDF](pdf/101842_com_762_male_without_photo_43_2025-11-24.pdf) | [JSON](json/101842_com_762_male_without_photo_43_2025-11-24.json) |
| 34 | `101842` | আগুনিয়াতাইড় (উত্তর) | 51 | 879 | 876 | [PDF](pdf/101842_com_876_female_without_photo_49_2025-11-24.pdf) | [JSON](json/101842_com_876_female_without_photo_49_2025-11-24.json) |
| 35 | `101843` | আগুনিয়াতাইড় (দক্ষিণ) | 67 | 1,167 | 1,157 | [PDF](pdf/101843_com_1157_male_without_photo_65_2025-11-24.pdf) | [JSON](json/101843_com_1157_male_without_photo_65_2025-11-24.json) |
| 36 | `101843` | আগুনিয়াতাইড় (দক্ষিণ) | 76 | 1,320 | 1,319 | [PDF](pdf/101843_com_1319_female_without_photo_74_2025-11-24.pdf) | [JSON](json/101843_com_1319_female_without_photo_74_2025-11-24.json) |
| 37 | `101844` | কামারপাড়া | 80 | 1,400 | 1,394 | [PDF](pdf/101844_com_1394_male_without_photo_78_2025-11-24.pdf) | [JSON](json/101844_com_1394_male_without_photo_78_2025-11-24.json) |
| 38 | `101844` | কামারপাড়া | 87 | 1,517 | 1,512 | [PDF](pdf/101844_com_1512_female_without_photo_85_2025-11-24.pdf) | [JSON](json/101844_com_1512_female_without_photo_85_2025-11-24.json) |
| 39 | `101845` | কানুপুর | 48 | 825 | 822 | [PDF](pdf/101845_com_822_male_without_photo_46_2025-11-24.pdf) | [JSON](json/101845_com_822_male_without_photo_46_2025-11-24.json) |
| 40 | `101845` | কানুপুর | 50 | 860 | 860 | [PDF](pdf/101845_com_860_female_without_photo_48_2025-11-24.pdf) | [JSON](json/101845_com_860_female_without_photo_48_2025-11-24.json) |
| 41 | `101846` | বোচারপুকুর | 14 | 212 | 212 | [PDF](pdf/101846_com_212_male_without_photo_12_2025-11-24.pdf) | [JSON](json/101846_com_212_male_without_photo_12_2025-11-24.json) |
| 42 | `101846` | বোচারপুকুর | 15 | 222 | 221 | [PDF](pdf/101846_com_221_female_without_photo_13_2025-11-24.pdf) | [JSON](json/101846_com_221_female_without_photo_13_2025-11-24.json) |
