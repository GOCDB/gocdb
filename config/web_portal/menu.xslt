<?xml version="1.0"?>
<!--
  - Generate the schema definition for valid menu elements for including
  - into config/local_info.xsd
  -
  - Example: # xsltproc -o menu.xsd menu.xslt menu.xml
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" indent="yes" omit-xml-declaration="no"/>

<xsl:strip-space elements="*"/>

<xsl:template match="/menus/main_menu">
<xsl:comment>
  ** DO NOT EDIT THIS FILE DIRECTLY. **
  ** THIS FILE IS AUTO GENERATED.    **
  ** SEE menu.xslt FOR DETAILS.      **
</xsl:comment>
  <xsl:text disable-output-escaping="yes">&lt;xs:schema version="1.0"
      xmlns:xs="http://www.w3.org/2001/XMLSchema"
      &gt;</xsl:text>
  <xsl:text disable-output-escaping="yes">
  &lt;xs:complexType name="validMenus"&gt;
    &lt;xs:all&gt;</xsl:text>
  <xsl:apply-templates/>
  <xsl:text disable-output-escaping="yes">
    &lt;/xs:all&gt;
  &lt;/xs:complexType&gt;
&lt;/xs:schema&gt;&#xa;</xsl:text>
</xsl:template>

<xsl:template match="/menus/main_menu/*[not(self::spacer)]">
<!--
  It would be better not to have to specify minOccurs repeatedly
  but it is/seems to be necessary to allow the override sections not to be fully
  specified.
-->
  <xsl:text disable-output-escaping="yes">
        &lt;xs:element name="</xsl:text>
  <xsl:value-of select="name()"/>
  <xsl:text disable-output-escaping="yes">" type="showType" minOccurs="0"/&gt;</xsl:text>
</xsl:template>

<xsl:template match="/menus/main_menu/spacer">
</xsl:template>

</xsl:stylesheet>
