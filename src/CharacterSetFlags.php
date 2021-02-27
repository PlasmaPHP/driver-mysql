<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

/**
 * The MySQL character set flags.
 */
interface CharacterSetFlags {
    /**
     * Collation `big5_chinese_ci`
     * @var int
     * @source
     */
    const CHARSET_BIG5_CHINESE_CI = 1;
    
    /**
     * Collation `latin2_czech_cs`
     * @var int
     * @source
     */
    const CHARSET_LATIN2_CZECH_CS = 2;
    
    /**
     * Collation `dec8_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_DEC8_SWEDISH_CI = 3;
    
    /**
     * Collation `cp850_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP850_GENERAL_CI = 4;
    
    /**
     * Collation `latin1_german1_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_GERMAN1_CI = 5;
    
    /**
     * Collation `hp8_english_ci`
     * @var int
     * @source
     */
    const CHARSET_HP8_ENGLISH_CI = 6;
    
    /**
     * Collation `koi8r_general_ci`
     * @var int
     * @source
     */
    const CHARSET_KOI8R_GENERAL_CI = 7;
    
    /**
     * Collation `latin1_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_SWEDISH_CI = 8;
    
    /**
     * Collation `latin2_general_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN2_GENERAL_CI = 9;
    
    /**
     * Collation `swe7_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_SWE7_SWEDISH_CI = 10;
    
    /**
     * Collation `ascii_general_ci`
     * @var int
     * @source
     */
    const CHARSET_ASCII_GENERAL_CI = 11;
    
    /**
     * Collation `ujis_japanese_ci`
     * @var int
     * @source
     */
    const CHARSET_UJIS_JAPANESE_CI = 12;
    
    /**
     * Collation `sjis_japanese_ci`
     * @var int
     * @source
     */
    const CHARSET_SJIS_JAPANESE_CI = 13;
    
    /**
     * Collation `cp1251_bulgarian_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1251_BULGARIAN_CI = 14;
    
    /**
     * Collation `latin1_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_DANISH_CI = 15;
    
    /**
     * Collation `hebrew_general_ci`
     * @var int
     * @source
     */
    const CHARSET_HEBREW_GENERAL_CI = 16;
    
    /**
     * Collation `tis620_thai_ci`
     * @var int
     * @source
     */
    const CHARSET_TIS620_THAI_CI = 18;
    
    /**
     * Collation `euckr_korean_ci`
     * @var int
     * @source
     */
    const CHARSET_EUCKR_KOREAN_CI = 19;
    
    /**
     * Collation `latin7_estonian_cs`
     * @var int
     * @source
     */
    const CHARSET_LATIN7_ESTONIAN_CS = 20;
    
    /**
     * Collation `latin2_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN2_HUNGARIAN_CI = 21;
    
    /**
     * Collation `koi8u_general_ci`
     * @var int
     * @source
     */
    const CHARSET_KOI8U_GENERAL_CI = 22;
    
    /**
     * Collation `cp1251_ukrainian_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1251_UKRAINIAN_CI = 23;
    
    /**
     * Collation `gb2312_chinese_ci`
     * @var int
     * @source
     */
    const CHARSET_GB2312_CHINESE_CI = 24;
    
    /**
     * Collation `greek_general_ci`
     * @var int
     * @source
     */
    const CHARSET_GREEK_GENERAL_CI = 25;
    
    /**
     * Collation `cp1250_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1250_GENERAL_CI = 26;
    
    /**
     * Collation `latin2_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN2_CROATIAN_CI = 27;
    
    /**
     * Collation `gbk_chinese_ci`
     * @var int
     * @source
     */
    const CHARSET_GBK_CHINESE_CI = 28;
    
    /**
     * Collation `cp1257_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1257_LITHUANIAN_CI = 29;
    
    /**
     * Collation `latin5_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN5_TURKISH_CI = 30;
    
    /**
     * Collation `latin1_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_GERMAN2_CI = 31;
    
    /**
     * Collation `armscii8_general_ci`
     * @var int
     * @source
     */
    const CHARSET_ARMSCII8_GENERAL_CI = 32;
    
    /**
     * Collation `utf8_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_GENERAL_CI = 33;
    
    /**
     * Collation `cp1250_czech_cs`
     * @var int
     * @source
     */
    const CHARSET_CP1250_CZECH_CS = 34;
    
    /**
     * Collation `ucs2_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_GENERAL_CI = 35;
    
    /**
     * Collation `cp866_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP866_GENERAL_CI = 36;
    
    /**
     * Collation `keybcs2_general_ci`
     * @var int
     * @source
     */
    const CHARSET_KEYBCS2_GENERAL_CI = 37;
    
    /**
     * Collation `macce_general_ci`
     * @var int
     * @source
     */
    const CHARSET_MACCE_GENERAL_CI = 38;
    
    /**
     * Collation `macroman_general_ci`
     * @var int
     * @source
     */
    const CHARSET_MACROMAN_GENERAL_CI = 39;
    
    /**
     * Collation `cp852_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP852_GENERAL_CI = 40;
    
    /**
     * Collation `latin7_general_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN7_GENERAL_CI = 41;
    
    /**
     * Collation `latin7_general_cs`
     * @var int
     * @source
     */
    const CHARSET_LATIN7_GENERAL_CS = 42;
    
    /**
     * Collation `macce_bin`
     * @var int
     * @source
     */
    const CHARSET_MACCE_BIN = 43;
    
    /**
     * Collation `cp1250_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1250_CROATIAN_CI = 44;
    
    /**
     * Collation `utf8mb4_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_GENERAL_CI = 45;
    
    /**
     * Collation `utf8mb4_bin`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_BIN = 46;
    
    /**
     * Collation `latin1_bin`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_BIN = 47;
    
    /**
     * Collation `latin1_general_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_GENERAL_CI = 48;
    
    /**
     * Collation `latin1_general_cs`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_GENERAL_CS = 49;
    
    /**
     * Collation `cp1251_bin`
     * @var int
     * @source
     */
    const CHARSET_CP1251_BIN = 50;
    
    /**
     * Collation `cp1251_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1251_GENERAL_CI = 51;
    
    /**
     * Collation `cp1251_general_cs`
     * @var int
     * @source
     */
    const CHARSET_CP1251_GENERAL_CS = 52;
    
    /**
     * Collation `macroman_bin`
     * @var int
     * @source
     */
    const CHARSET_MACROMAN_BIN = 53;
    
    /**
     * Collation `utf16_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_GENERAL_CI = 54;
    
    /**
     * Collation `utf16_bin`
     * @var int
     * @source
     */
    const CHARSET_UTF16_BIN = 55;
    
    /**
     * Collation `utf16le_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16LE_GENERAL_CI = 56;
    
    /**
     * Collation `cp1256_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1256_GENERAL_CI = 57;
    
    /**
     * Collation `cp1257_bin`
     * @var int
     * @source
     */
    const CHARSET_CP1257_BIN = 58;
    
    /**
     * Collation `cp1257_general_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1257_GENERAL_CI = 59;
    
    /**
     * Collation `utf32_general_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_GENERAL_CI = 60;
    
    /**
     * Collation `utf32_bin`
     * @var int
     * @source
     */
    const CHARSET_UTF32_BIN = 61;
    
    /**
     * Collation `utf16le_bin`
     * @var int
     * @source
     */
    const CHARSET_UTF16LE_BIN = 62;
    
    /**
     * Collation `binary`
     * @var int
     * @source
     */
    const CHARSET_BINARY = 63;
    
    /**
     * Collation `armscii8_bin`
     * @var int
     * @source
     */
    const CHARSET_ARMSCII8_BIN = 64;
    
    /**
     * Collation `ascii_bin`
     * @var int
     * @source
     */
    const CHARSET_ASCII_BIN = 65;
    
    /**
     * Collation `cp1250_bin`
     * @var int
     * @source
     */
    const CHARSET_CP1250_BIN = 66;
    
    /**
     * Collation `cp1256_bin`
     * @var int
     * @source
     */
    const CHARSET_CP1256_BIN = 67;
    
    /**
     * Collation `cp866_bin`
     * @var int
     * @source
     */
    const CHARSET_CP866_BIN = 68;
    
    /**
     * Collation `dec8_bin`
     * @var int
     * @source
     */
    const CHARSET_DEC8_BIN = 69;
    
    /**
     * Collation `greek_bin`
     * @var int
     * @source
     */
    const CHARSET_GREEK_BIN = 70;
    
    /**
     * Collation `hebrew_bin`
     * @var int
     * @source
     */
    const CHARSET_HEBREW_BIN = 71;
    
    /**
     * Collation `hp8_bin`
     * @var int
     * @source
     */
    const CHARSET_HP8_BIN = 72;
    
    /**
     * Collation `keybcs2_bin`
     * @var int
     * @source
     */
    const CHARSET_KEYBCS2_BIN = 73;
    
    /**
     * Collation `koi8r_bin`
     * @var int
     * @source
     */
    const CHARSET_KOI8R_BIN = 74;
    
    /**
     * Collation `koi8u_bin`
     * @var int
     * @source
     */
    const CHARSET_KOI8U_BIN = 75;
    
    /**
     * Collation `latin2_bin`
     * @var int
     * @source
     */
    const CHARSET_LATIN2_BIN = 77;
    
    /**
     * Collation `latin5_bin`
     * @var int
     * @source
     */
    const CHARSET_LATIN5_BIN = 78;
    
    /**
     * Collation `latin7_bin`
     * @var int
     * @source
     */
    const CHARSET_LATIN7_BIN = 79;
    
    /**
     * Collation `cp850_bin`
     * @var int
     * @source
     */
    const CHARSET_CP850_BIN = 80;
    
    /**
     * Collation `cp852_bin`
     * @var int
     * @source
     */
    const CHARSET_CP852_BIN = 81;
    
    /**
     * Collation `swe7_bin`
     * @var int
     * @source
     */
    const CHARSET_SWE7_BIN = 82;
    
    /**
     * Collation `utf8_bin`
     * @var int
     * @source
     */
    const CHARSET_UTF8_BIN = 83;
    
    /**
     * Collation `big5_bin`
     * @var int
     * @source
     */
    const CHARSET_BIG5_BIN = 84;
    
    /**
     * Collation `euckr_bin`
     * @var int
     * @source
     */
    const CHARSET_EUCKR_BIN = 85;
    
    /**
     * Collation `gb2312_bin`
     * @var int
     * @source
     */
    const CHARSET_GB2312_BIN = 86;
    
    /**
     * Collation `gbk_bin`
     * @var int
     * @source
     */
    const CHARSET_GBK_BIN = 87;
    
    /**
     * Collation `sjis_bin`
     * @var int
     * @source
     */
    const CHARSET_SJIS_BIN = 88;
    
    /**
     * Collation `tis620_bin`
     * @var int
     * @source
     */
    const CHARSET_TIS620_BIN = 89;
    
    /**
     * Collation `ucs2_bin`
     * @var int
     * @source
     */
    const CHARSET_UCS2_BIN = 90;
    
    /**
     * Collation `ujis_bin`
     * @var int
     * @source
     */
    const CHARSET_UJIS_BIN = 91;
    
    /**
     * Collation `geostd8_general_ci`
     * @var int
     * @source
     */
    const CHARSET_GEOSTD8_GENERAL_CI = 92;
    
    /**
     * Collation `geostd8_bin`
     * @var int
     * @source
     */
    const CHARSET_GEOSTD8_BIN = 93;
    
    /**
     * Collation `latin1_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_LATIN1_SPANISH_CI = 94;
    
    /**
     * Collation `cp932_japanese_ci`
     * @var int
     * @source
     */
    const CHARSET_CP932_JAPANESE_CI = 95;
    
    /**
     * Collation `cp932_bin`
     * @var int
     * @source
     */
    const CHARSET_CP932_BIN = 96;
    
    /**
     * Collation `eucjpms_japanese_ci`
     * @var int
     * @source
     */
    const CHARSET_EUCJPMS_JAPANESE_CI = 97;
    
    /**
     * Collation `eucjpms_bin`
     * @var int
     * @source
     */
    const CHARSET_EUCJPMS_BIN = 98;
    
    /**
     * Collation `cp1250_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_CP1250_POLISH_CI = 99;
    
    /**
     * Collation `utf16_unicode_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_UNICODE_CI = 101;
    
    /**
     * Collation `utf16_icelandic_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_ICELANDIC_CI = 102;
    
    /**
     * Collation `utf16_latvian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_LATVIAN_CI = 103;
    
    /**
     * Collation `utf16_romanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_ROMANIAN_CI = 104;
    
    /**
     * Collation `utf16_slovenian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SLOVENIAN_CI = 105;
    
    /**
     * Collation `utf16_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_POLISH_CI = 106;
    
    /**
     * Collation `utf16_estonian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_ESTONIAN_CI = 107;
    
    /**
     * Collation `utf16_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SPANISH_CI = 108;
    
    /**
     * Collation `utf16_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SWEDISH_CI = 109;
    
    /**
     * Collation `utf16_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_TURKISH_CI = 110;
    
    /**
     * Collation `utf16_czech_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_CZECH_CI = 111;
    
    /**
     * Collation `utf16_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_DANISH_CI = 112;
    
    /**
     * Collation `utf16_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_LITHUANIAN_CI = 113;
    
    /**
     * Collation `utf16_slovak_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SLOVAK_CI = 114;
    
    /**
     * Collation `utf16_spanish2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SPANISH2_CI = 115;
    
    /**
     * Collation `utf16_roman_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_ROMAN_CI = 116;
    
    /**
     * Collation `utf16_persian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_PERSIAN_CI = 117;
    
    /**
     * Collation `utf16_esperanto_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_ESPERANTO_CI = 118;
    
    /**
     * Collation `utf16_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_HUNGARIAN_CI = 119;
    
    /**
     * Collation `utf16_sinhala_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_SINHALA_CI = 120;
    
    /**
     * Collation `utf16_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_GERMAN2_CI = 121;
    
    /**
     * Collation `utf16_croatian_mysql561_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_CROATIAN_MYSQL561_CI = 122;
    
    /**
     * Collation `utf16_unicode_520_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_UNICODE_520_CI = 123;
    
    /**
     * Collation `utf16_vietnamese_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_VIETNAMESE_CI = 124;
    
    /**
     * Collation `ucs2_unicode_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_UNICODE_CI = 128;
    
    /**
     * Collation `ucs2_icelandic_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_ICELANDIC_CI = 129;
    
    /**
     * Collation `ucs2_latvian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_LATVIAN_CI = 130;
    
    /**
     * Collation `ucs2_romanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_ROMANIAN_CI = 131;
    
    /**
     * Collation `ucs2_slovenian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SLOVENIAN_CI = 132;
    
    /**
     * Collation `ucs2_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_POLISH_CI = 133;
    
    /**
     * Collation `ucs2_estonian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_ESTONIAN_CI = 134;
    
    /**
     * Collation `ucs2_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SPANISH_CI = 135;
    
    /**
     * Collation `ucs2_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SWEDISH_CI = 136;
    
    /**
     * Collation `ucs2_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_TURKISH_CI = 137;
    
    /**
     * Collation `ucs2_czech_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_CZECH_CI = 138;
    
    /**
     * Collation `ucs2_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_DANISH_CI = 139;
    
    /**
     * Collation `ucs2_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_LITHUANIAN_CI = 140;
    
    /**
     * Collation `ucs2_slovak_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SLOVAK_CI = 141;
    
    /**
     * Collation `ucs2_spanish2_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SPANISH2_CI = 142;
    
    /**
     * Collation `ucs2_roman_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_ROMAN_CI = 143;
    
    /**
     * Collation `ucs2_persian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_PERSIAN_CI = 144;
    
    /**
     * Collation `ucs2_esperanto_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_ESPERANTO_CI = 145;
    
    /**
     * Collation `ucs2_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_HUNGARIAN_CI = 146;
    
    /**
     * Collation `ucs2_sinhala_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_SINHALA_CI = 147;
    
    /**
     * Collation `ucs2_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_GERMAN2_CI = 148;
    
    /**
     * Collation `ucs2_croatian_mysql561_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_CROATIAN_MYSQL561_CI = 149;
    
    /**
     * Collation `ucs2_unicode_520_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_UNICODE_520_CI = 150;
    
    /**
     * Collation `ucs2_vietnamese_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_VIETNAMESE_CI = 151;
    
    /**
     * Collation `ucs2_general_mysql500_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_GENERAL_MYSQL500_CI = 159;
    
    /**
     * Collation `utf32_unicode_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_UNICODE_CI = 160;
    
    /**
     * Collation `utf32_icelandic_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_ICELANDIC_CI = 161;
    
    /**
     * Collation `utf32_latvian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_LATVIAN_CI = 162;
    
    /**
     * Collation `utf32_romanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_ROMANIAN_CI = 163;
    
    /**
     * Collation `utf32_slovenian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SLOVENIAN_CI = 164;
    
    /**
     * Collation `utf32_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_POLISH_CI = 165;
    
    /**
     * Collation `utf32_estonian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_ESTONIAN_CI = 166;
    
    /**
     * Collation `utf32_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SPANISH_CI = 167;
    
    /**
     * Collation `utf32_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SWEDISH_CI = 168;
    
    /**
     * Collation `utf32_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_TURKISH_CI = 169;
    
    /**
     * Collation `utf32_czech_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_CZECH_CI = 170;
    
    /**
     * Collation `utf32_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_DANISH_CI = 171;
    
    /**
     * Collation `utf32_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_LITHUANIAN_CI = 172;
    
    /**
     * Collation `utf32_slovak_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SLOVAK_CI = 173;
    
    /**
     * Collation `utf32_spanish2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SPANISH2_CI = 174;
    
    /**
     * Collation `utf32_roman_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_ROMAN_CI = 175;
    
    /**
     * Collation `utf32_persian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_PERSIAN_CI = 176;
    
    /**
     * Collation `utf32_esperanto_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_ESPERANTO_CI = 177;
    
    /**
     * Collation `utf32_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_HUNGARIAN_CI = 178;
    
    /**
     * Collation `utf32_sinhala_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_SINHALA_CI = 179;
    
    /**
     * Collation `utf32_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_GERMAN2_CI = 180;
    
    /**
     * Collation `utf32_croatian_mysql561_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_CROATIAN_MYSQL561_CI = 181;
    
    /**
     * Collation `utf32_unicode_520_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_UNICODE_520_CI = 182;
    
    /**
     * Collation `utf32_vietnamese_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_VIETNAMESE_CI = 183;
    
    /**
     * Collation `utf8_unicode_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_UNICODE_CI = 192;
    
    /**
     * Collation `utf8_icelandic_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_ICELANDIC_CI = 193;
    
    /**
     * Collation `utf8_latvian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_LATVIAN_CI = 194;
    
    /**
     * Collation `utf8_romanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_ROMANIAN_CI = 195;
    
    /**
     * Collation `utf8_slovenian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SLOVENIAN_CI = 196;
    
    /**
     * Collation `utf8_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_POLISH_CI = 197;
    
    /**
     * Collation `utf8_estonian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_ESTONIAN_CI = 198;
    
    /**
     * Collation `utf8_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SPANISH_CI = 199;
    
    /**
     * Collation `utf8_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SWEDISH_CI = 200;
    
    /**
     * Collation `utf8_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_TURKISH_CI = 201;
    
    /**
     * Collation `utf8_czech_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_CZECH_CI = 202;
    
    /**
     * Collation `utf8_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_DANISH_CI = 203;
    
    /**
     * Collation `utf8_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_LITHUANIAN_CI = 204;
    
    /**
     * Collation `utf8_slovak_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SLOVAK_CI = 205;
    
    /**
     * Collation `utf8_spanish2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SPANISH2_CI = 206;
    
    /**
     * Collation `utf8_roman_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_ROMAN_CI = 207;
    
    /**
     * Collation `utf8_persian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_PERSIAN_CI = 208;
    
    /**
     * Collation `utf8_esperanto_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_ESPERANTO_CI = 209;
    
    /**
     * Collation `utf8_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_HUNGARIAN_CI = 210;
    
    /**
     * Collation `utf8_sinhala_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_SINHALA_CI = 211;
    
    /**
     * Collation `utf8_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_GERMAN2_CI = 212;
    
    /**
     * Collation `utf8_croatian_mysql561_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_CROATIAN_MYSQL561_CI = 213;
    
    /**
     * Collation `utf8_unicode_520_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_UNICODE_520_CI = 214;
    
    /**
     * Collation `utf8_vietnamese_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_VIETNAMESE_CI = 215;
    
    /**
     * Collation `utf8_general_mysql500_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_GENERAL_MYSQL500_CI = 223;
    
    /**
     * Collation `utf8mb4_unicode_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_UNICODE_CI = 224;
    
    /**
     * Collation `utf8mb4_icelandic_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_ICELANDIC_CI = 225;
    
    /**
     * Collation `utf8mb4_latvian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_LATVIAN_CI = 226;
    
    /**
     * Collation `utf8mb4_romanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_ROMANIAN_CI = 227;
    
    /**
     * Collation `utf8mb4_slovenian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SLOVENIAN_CI = 228;
    
    /**
     * Collation `utf8mb4_polish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_POLISH_CI = 229;
    
    /**
     * Collation `utf8mb4_estonian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_ESTONIAN_CI = 230;
    
    /**
     * Collation `utf8mb4_spanish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SPANISH_CI = 231;
    
    /**
     * Collation `utf8mb4_swedish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SWEDISH_CI = 232;
    
    /**
     * Collation `utf8mb4_turkish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_TURKISH_CI = 233;
    
    /**
     * Collation `utf8mb4_czech_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_CZECH_CI = 234;
    
    /**
     * Collation `utf8mb4_danish_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_DANISH_CI = 235;
    
    /**
     * Collation `utf8mb4_lithuanian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_LITHUANIAN_CI = 236;
    
    /**
     * Collation `utf8mb4_slovak_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SLOVAK_CI = 237;
    
    /**
     * Collation `utf8mb4_spanish2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SPANISH2_CI = 238;
    
    /**
     * Collation `utf8mb4_roman_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_ROMAN_CI = 239;
    
    /**
     * Collation `utf8mb4_persian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_PERSIAN_CI = 240;
    
    /**
     * Collation `utf8mb4_esperanto_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_ESPERANTO_CI = 241;
    
    /**
     * Collation `utf8mb4_hungarian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_HUNGARIAN_CI = 242;
    
    /**
     * Collation `utf8mb4_sinhala_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_SINHALA_CI = 243;
    
    /**
     * Collation `utf8mb4_german2_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_GERMAN2_CI = 244;
    
    /**
     * Collation `utf8mb4_croatian_mysql561_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_CROATIAN_MYSQL561_CI = 245;
    
    /**
     * Collation `utf8mb4_unicode_520_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_UNICODE_520_CI = 246;
    
    /**
     * Collation `utf8mb4_vietnamese_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_VIETNAMESE_CI = 247;
    
    /**
     * Collation `utf8_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_CROATIAN_CI = 576;
    
    /**
     * Collation `utf8_myanmar_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8_MYANMAR_CI = 577;
    
    /**
     * Collation `utf8mb4_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_CROATIAN_CI = 608;
    
    /**
     * Collation `utf8mb4_myanmar_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF8MB4_MYANMAR_CI = 609;
    
    /**
     * Collation `ucs2_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_CROATIAN_CI = 640;
    
    /**
     * Collation `ucs2_myanmar_ci`
     * @var int
     * @source
     */
    const CHARSET_UCS2_MYANMAR_CI = 641;
    
    /**
     * Collation `utf16_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_CROATIAN_CI = 672;
    
    /**
     * Collation `utf16_myanmar_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF16_MYANMAR_CI = 673;
    
    /**
     * Collation `utf32_croatian_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_CROATIAN_CI = 736;
    
    /**
     * Collation `utf32_myanmar_ci`
     * @var int
     * @source
     */
    const CHARSET_UTF32_MYANMAR_CI = 737;
    
    /**
     * Charset int to name map.
     * @var string[]
     * @source
     */
    const CHARSET_MAP = array(
        1 => 'big5_chinese_ci',
        2 => 'latin2_czech_cs',
        3 => 'dec8_swedish_ci',
        4 => 'cp850_general_ci',
        5 => 'latin1_german1_ci',
        6 => 'hp8_english_ci',
        7 => 'koi8r_general_ci',
        8 => 'latin1_swedish_ci',
        9 => 'latin2_general_ci',
        10 => 'swe7_swedish_ci',
        11 => 'ascii_general_ci',
        12 => 'ujis_japanese_ci',
        13 => 'sjis_japanese_ci',
        14 => 'cp1251_bulgarian_ci',
        15 => 'latin1_danish_ci',
        16 => 'hebrew_general_ci',
        18 => 'tis620_thai_ci',
        19 => 'euckr_korean_ci',
        20 => 'latin7_estonian_cs',
        21 => 'latin2_hungarian_ci',
        22 => 'koi8u_general_ci',
        23 => 'cp1251_ukrainian_ci',
        24 => 'gb2312_chinese_ci',
        25 => 'greek_general_ci',
        26 => 'cp1250_general_ci',
        27 => 'latin2_croatian_ci',
        28 => 'gbk_chinese_ci',
        29 => 'cp1257_lithuanian_ci',
        30 => 'latin5_turkish_ci',
        31 => 'latin1_german2_ci',
        32 => 'armscii8_general_ci',
        33 => 'utf8_general_ci',
        34 => 'cp1250_czech_cs',
        35 => 'ucs2_general_ci',
        36 => 'cp866_general_ci',
        37 => 'keybcs2_general_ci',
        38 => 'macce_general_ci',
        39 => 'macroman_general_ci',
        40 => 'cp852_general_ci',
        41 => 'latin7_general_ci',
        42 => 'latin7_general_cs',
        43 => 'macce_bin',
        44 => 'cp1250_croatian_ci',
        45 => 'utf8mb4_general_ci',
        46 => 'utf8mb4_bin',
        47 => 'latin1_bin',
        48 => 'latin1_general_ci',
        49 => 'latin1_general_cs',
        50 => 'cp1251_bin',
        51 => 'cp1251_general_ci',
        52 => 'cp1251_general_cs',
        53 => 'macroman_bin',
        54 => 'utf16_general_ci',
        55 => 'utf16_bin',
        56 => 'utf16le_general_ci',
        57 => 'cp1256_general_ci',
        58 => 'cp1257_bin',
        59 => 'cp1257_general_ci',
        60 => 'utf32_general_ci',
        61 => 'utf32_bin',
        62 => 'utf16le_bin',
        63 => 'binary',
        64 => 'armscii8_bin',
        65 => 'ascii_bin',
        66 => 'cp1250_bin',
        67 => 'cp1256_bin',
        68 => 'cp866_bin',
        69 => 'dec8_bin',
        70 => 'greek_bin',
        71 => 'hebrew_bin',
        72 => 'hp8_bin',
        73 => 'keybcs2_bin',
        74 => 'koi8r_bin',
        75 => 'koi8u_bin',
        77 => 'latin2_bin',
        78 => 'latin5_bin',
        79 => 'latin7_bin',
        80 => 'cp850_bin',
        81 => 'cp852_bin',
        82 => 'swe7_bin',
        83 => 'utf8_bin',
        84 => 'big5_bin',
        85 => 'euckr_bin',
        86 => 'gb2312_bin',
        87 => 'gbk_bin',
        88 => 'sjis_bin',
        89 => 'tis620_bin',
        90 => 'ucs2_bin',
        91 => 'ujis_bin',
        92 => 'geostd8_general_ci',
        93 => 'geostd8_bin',
        94 => 'latin1_spanish_ci',
        95 => 'cp932_japanese_ci',
        96 => 'cp932_bin',
        97 => 'eucjpms_japanese_ci',
        98 => 'eucjpms_bin',
        99 => 'cp1250_polish_ci',
        101 => 'utf16_unicode_ci',
        102 => 'utf16_icelandic_ci',
        103 => 'utf16_latvian_ci',
        104 => 'utf16_romanian_ci',
        105 => 'utf16_slovenian_ci',
        106 => 'utf16_polish_ci',
        107 => 'utf16_estonian_ci',
        108 => 'utf16_spanish_ci',
        109 => 'utf16_swedish_ci',
        110 => 'utf16_turkish_ci',
        111 => 'utf16_czech_ci',
        112 => 'utf16_danish_ci',
        113 => 'utf16_lithuanian_ci',
        114 => 'utf16_slovak_ci',
        115 => 'utf16_spanish2_ci',
        116 => 'utf16_roman_ci',
        117 => 'utf16_persian_ci',
        118 => 'utf16_esperanto_ci',
        119 => 'utf16_hungarian_ci',
        120 => 'utf16_sinhala_ci',
        121 => 'utf16_german2_ci',
        122 => 'utf16_croatian_mysql561_ci',
        123 => 'utf16_unicode_520_ci',
        124 => 'utf16_vietnamese_ci',
        128 => 'ucs2_unicode_ci',
        129 => 'ucs2_icelandic_ci',
        130 => 'ucs2_latvian_ci',
        131 => 'ucs2_romanian_ci',
        132 => 'ucs2_slovenian_ci',
        133 => 'ucs2_polish_ci',
        134 => 'ucs2_estonian_ci',
        135 => 'ucs2_spanish_ci',
        136 => 'ucs2_swedish_ci',
        137 => 'ucs2_turkish_ci',
        138 => 'ucs2_czech_ci',
        139 => 'ucs2_danish_ci',
        140 => 'ucs2_lithuanian_ci',
        141 => 'ucs2_slovak_ci',
        142 => 'ucs2_spanish2_ci',
        143 => 'ucs2_roman_ci',
        144 => 'ucs2_persian_ci',
        145 => 'ucs2_esperanto_ci',
        146 => 'ucs2_hungarian_ci',
        147 => 'ucs2_sinhala_ci',
        148 => 'ucs2_german2_ci',
        149 => 'ucs2_croatian_mysql561_ci',
        150 => 'ucs2_unicode_520_ci',
        151 => 'ucs2_vietnamese_ci',
        159 => 'ucs2_general_mysql500_ci',
        160 => 'utf32_unicode_ci',
        161 => 'utf32_icelandic_ci',
        162 => 'utf32_latvian_ci',
        163 => 'utf32_romanian_ci',
        164 => 'utf32_slovenian_ci',
        165 => 'utf32_polish_ci',
        166 => 'utf32_estonian_ci',
        167 => 'utf32_spanish_ci',
        168 => 'utf32_swedish_ci',
        169 => 'utf32_turkish_ci',
        170 => 'utf32_czech_ci',
        171 => 'utf32_danish_ci',
        172 => 'utf32_lithuanian_ci',
        173 => 'utf32_slovak_ci',
        174 => 'utf32_spanish2_ci',
        175 => 'utf32_roman_ci',
        176 => 'utf32_persian_ci',
        177 => 'utf32_esperanto_ci',
        178 => 'utf32_hungarian_ci',
        179 => 'utf32_sinhala_ci',
        180 => 'utf32_german2_ci',
        181 => 'utf32_croatian_mysql561_ci',
        182 => 'utf32_unicode_520_ci',
        183 => 'utf32_vietnamese_ci',
        192 => 'utf8_unicode_ci',
        193 => 'utf8_icelandic_ci',
        194 => 'utf8_latvian_ci',
        195 => 'utf8_romanian_ci',
        196 => 'utf8_slovenian_ci',
        197 => 'utf8_polish_ci',
        198 => 'utf8_estonian_ci',
        199 => 'utf8_spanish_ci',
        200 => 'utf8_swedish_ci',
        201 => 'utf8_turkish_ci',
        202 => 'utf8_czech_ci',
        203 => 'utf8_danish_ci',
        204 => 'utf8_lithuanian_ci',
        205 => 'utf8_slovak_ci',
        206 => 'utf8_spanish2_ci',
        207 => 'utf8_roman_ci',
        208 => 'utf8_persian_ci',
        209 => 'utf8_esperanto_ci',
        210 => 'utf8_hungarian_ci',
        211 => 'utf8_sinhala_ci',
        212 => 'utf8_german2_ci',
        213 => 'utf8_croatian_mysql561_ci',
        214 => 'utf8_unicode_520_ci',
        215 => 'utf8_vietnamese_ci',
        223 => 'utf8_general_mysql500_ci',
        224 => 'utf8mb4_unicode_ci',
        225 => 'utf8mb4_icelandic_ci',
        226 => 'utf8mb4_latvian_ci',
        227 => 'utf8mb4_romanian_ci',
        228 => 'utf8mb4_slovenian_ci',
        229 => 'utf8mb4_polish_ci',
        230 => 'utf8mb4_estonian_ci',
        231 => 'utf8mb4_spanish_ci',
        232 => 'utf8mb4_swedish_ci',
        233 => 'utf8mb4_turkish_ci',
        234 => 'utf8mb4_czech_ci',
        235 => 'utf8mb4_danish_ci',
        236 => 'utf8mb4_lithuanian_ci',
        237 => 'utf8mb4_slovak_ci',
        238 => 'utf8mb4_spanish2_ci',
        239 => 'utf8mb4_roman_ci',
        240 => 'utf8mb4_persian_ci',
        241 => 'utf8mb4_esperanto_ci',
        242 => 'utf8mb4_hungarian_ci',
        243 => 'utf8mb4_sinhala_ci',
        244 => 'utf8mb4_german2_ci',
        245 => 'utf8mb4_croatian_mysql561_ci',
        246 => 'utf8mb4_unicode_520_ci',
        247 => 'utf8mb4_vietnamese_ci',
        576 => 'utf8_croatian_ci',
        577 => 'utf8_myanmar_ci',
        608 => 'utf8mb4_croatian_ci',
        609 => 'utf8mb4_myanmar_ci',
        640 => 'ucs2_croatian_ci',
        641 => 'ucs2_myanmar_ci',
        672 => 'utf16_croatian_ci',
        673 => 'utf16_myanmar_ci',
        736 => 'utf32_croatian_ci',
        737 => 'utf32_myanmar_ci'
    );
}
