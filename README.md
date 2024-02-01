# image2TV
Build images for TV using IPTC as annotations

The default configuration in `image2TV.json` will convert the images found in `./images/` to slideshow images w. annotaions mirrored into `./TV/`

The default layout is build using the `image2TV.json` ([See below](#configuration_in_json))


## Test files
```text
images
│   Screenshot_2024-02-01-06-39-15-12.jpg  - Screenshot w only headline
│
├───Giza
│       2023-04-11T09-28-50_IMGP0824.JPG  - Some place in Egypt ;-)
│
├───Odense
│       IMG20240128114147.jpg  - Location in Denmark
│
└───Öland
        2023-07-30T13-29-00_IMGP1586.JPG - Location in Sweden
        2023-08-02T12-51-59_IMGP1800.JPG - Location in Sweden
        2023-08-03T16-18-06_IMGP1870.JPG - Location in Sweden
```

## Layout

The default layout is a canvas in 4K (3840x2160) with a resized image aligned to the left and metadata alinged to the right: 
![Layout diagram](http://www.plantuml.com/plantuml/proxy?cache=no&fmt=svg&src=https://raw.githubusercontent.com/Clicketyclick/image2TV/main/layout.puml)

Meta data is:

- IPTC:Headline (top right 5% from top)
- IPTC:Caption (middel right, 40% from top)
- IPTC:Location (bottom right, 80% from top)
- Flag (IPTC:CountryCode/Unknown) (bottom right)

<!--
![Embedded (public) diagram](http://www.plantuml.com/plantuml/proxy?cache=no&fmt=svg&src= + URL to public RAW)
-->

## Configuration in JSON

Tag|Default|Description
---|---|---
`@brief`				| `Image convertion to TV format`	|
 `@version`				| 2024-01-31 10|24|52				|
`verbose`				| 0									| Mute
`flag_dir`				| `flags/`							| Source dir for flags (xxx.png)
`source_path`			| `./images/`						| Source dir for images
`source_pattern`		| `.jpg`							| File pattern for source files
`target_path`			| `./TV/`							| Target root dir
`output_extention`		| `png`								| Output extentions
`output_type`			| `jpg`								| Output file type
`flag_unknown`			| `Nuvola_unknown_flag`				| Flag to display on missing IPTC:countrycode or unknown country
`max_height`			| 2160								| Max canvas height
`max_width`				| 3840								| Max canvas width
`background_color`		| `black`							| Background color
||
`boxwidth`				| 600								| Box width for meta data
||
`headline_start_vpos`	| 5									| Headline offset from top
`headline_font_size`	| 50								| Font size for headline
`headline_font_color`	| `yellow`							| Font color for headline
||
`caption_start_vpos`	| 40								| Caption  offset from top
`caption_font_size`		| 45								| Font size for caption
`caption_font_color`	| `white`							| Font color for caption
||
`location_start_vpos`	| 80								| Location  offset from top
`location_font_size`	| 30								| Font size for location
`location_font_color`	| `cyan`							| Font color for location
||
`flag_offset_x`			| 3700								| Offset for flag
`flag_offset_y`			| 2000								| Offset for flag
||
`image_offset_x`		| 0									| Start offset for image
`image_offset_y`		| 0									| Start offset for image



```json
{
    "@brief"                    : "Image convertion to TV format"
,    "@version"                 : "2024-01-31 10    :24    :52"
,    "verbose"                  : 0
,    "flag_dir"                 : "flags/"
,    "source_path"              : "./images/"
,    "source_pattern"           : ".jpg"
,    "target_path"              : "./TV/"
,    "output_extention"         : "png"
,    "output_type"              : "jpg"
,    "flag_unknown"             : "flag-unknown"
,    "flag_unknown"             : "Nuvola_unknown_flag"
,    "max_height"               : 2160
,    "max_width"                : 3840
,    "output_file"              : "meta_iptc"
,    "output_file_simple"       : "meta_blank"
,    "background_color"         : "black"

,    "boxwidth"                 : 600

,    "headline_start_vpos"      : 5
,    "headline_font_size"       : 50
,    "headline_font_color"      : "yellow"

,    "caption_start_vpos"       : 40
,    "caption_font_size"        : 45
,    "caption_font_color"       : "white"

,    "location_start_vpos"      : 80
,    "location_font_size"       : 30
,    "location_font_color"      : "cyan"

,    "flag_background_color"    : "black"
,    "flag_offset_x"            : 3700
,    "flag_offset_y"            : 2000

,    "image_offset_x"           : 0
,    "image_offset_y"           : 0
}
```

