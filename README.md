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

#### Styles ####
1. Upload CSL styles using bibcite to add more citation options

### Usage ###
1. Create a node as content type specified in the block
2. Add any required fields to node that correspond to what was set in CSL Format Mapping. Fields can be empty or non-existent.
3. Navigate to node page and choose citation format to view
