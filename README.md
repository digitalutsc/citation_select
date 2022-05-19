# Citation Select #
Adds a block that allows users to select and view citations of a node object from a list of citation styles.

## Setup and Usage ##
### Requirements ###
- Token module
- Bibcite module

### Installation ###
Download and enable module using file upload, Drush, or Composer

### Configuration ###
#### Block ####
1. Place block into Content under Structure › Block Layout
2. Select the content types that the block should appear in under Configure Block

#### Mapping ####
1. Navigate to CSL Format Mapping in Configuration
2. Select node fields to map citation fields from
    1. Mappings can be set for typed relations. For example, if the machine name of a relation is `rtl:aut`, that can be mapped to `author`, so that the correct format is used for citations
    2. Mappings can also be set for the "type" of citation (e.g. book, document). For example, if books are the content `paged content` in your systems, it can be mapped to `book`. If there is no [valid type](https://docs.citationstyles.org/en/stable/specification.html?#appendix-iii-types) or the field cannot be found, then the type is set to `document`

#### Styles ####
1. Upload CSL styles using bibcite to add more citation options

### Usage ###
1. Create a node as content type specified in the block
2. Add any required fields to node that correspond to what was set in CSL Format Mapping. Fields can be empty or non-existent.
3. Navigate to node page and choose citation format to view
