<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <xsl:output method="xml" indent="yes"/>

    <xsl:template match="/">
	<root>
	    <item>
		<xsl:attribute name="id">
		    <xsl:text>-1</xsl:text>
		</xsl:attribute>
		<xsl:attribute name="state">
		    <xsl:text>open</xsl:text>
		</xsl:attribute>
		<content>
		    <name>
			<xsl:text>all scanners</xsl:text>
		    </name>
		</content>
		<xsl:apply-templates select="/scanners/scanner[@ready='yes']"/>
	    </item>
	</root>
    </xsl:template>

    <xsl:template match="scanner">
	<item>
	    <xsl:attribute name="id">
		<xsl:value-of select="concat(@label,'-',@version)"/>
	    </xsl:attribute>
	    <content>
		<name>
		    <xsl:value-of select="./name"/>
		</name>
	    </content>
	</item>
    </xsl:template>
</xsl:stylesheet>
