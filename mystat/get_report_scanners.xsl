<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <xsl:output method="xml" indent="yes"/>

    <xsl:param name="scanners"/>
    <xsl:param name="comb_scanners"/>

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
		<xsl:call-template name="get_scanner">
		    <xsl:with-param name="scanner_id" select="substring-before($scanners, ',')"/>
		    <xsl:with-param name="other_scanners_id" select="substring-after($scanners, ',')"/>
		</xsl:call-template>
		<xsl:if test="$comb_scanners != ''">
		    <xsl:call-template name="get_comb_scanners">
			<xsl:with-param name="first_comb" select="substring-before($comb_scanners, ',')"/>
			<xsl:with-param name="other_combs" select="substring-after($comb_scanners, ',')"/>
		    </xsl:call-template>
		</xsl:if>
	    </item>
	</root>
    </xsl:template>

    <xsl:template name="get_scanner">
	<xsl:param name="scanner_id"/>
	<xsl:param name="other_scanners_id"/>
	<xsl:call-template name="scanner">
	    <xsl:with-param name="id" select="$scanner_id"/>
	</xsl:call-template>
	<xsl:if test="$other_scanners_id != ''">
	    <xsl:call-template name="get_scanner">
		<xsl:with-param name="scanner_id" select="substring-before($other_scanners_id, ',')"/>
		<xsl:with-param name="other_scanners_id" select="substring-after($other_scanners_id, ',')"/>
	    </xsl:call-template>
	</xsl:if>
    </xsl:template>

    <xsl:template name="scanner">
	<xsl:param name="id"/>
	<item>
	    <xsl:attribute name="id">
		<xsl:value-of select="$id"/>
	    </xsl:attribute>
	    <content>
		<name>
		    <xsl:value-of select="//scanner[@id=$id]/name"/>
		</name>
	    </content>
	</item>
    </xsl:template>


    <xsl:template name="get_comb_scanners">
	<xsl:param name="first_comb"/>
	<xsl:param name="other_combs"/>
	<xsl:call-template name="comb_scanners">
	    <xsl:with-param name="first_scanner_id" select="substring-before($first_comb,'+')"/>
	    <xsl:with-param name="second_scanner_id" select="substring-after($first_comb,'+')"/>
	</xsl:call-template>
	<xsl:if test="$other_combs != ''">
	    <xsl:call-template name="get_comb_scanners">
		<xsl:with-param name="first_comb" select="substring-before($other_combs,'+')"/>
		<xsl:with-param name="other_combs" select="substring-after($other_combs,'+')"/>
	    </xsl:call-template>
	</xsl:if>
    </xsl:template>

    <xsl:template name="comb_scanners">
	<xsl:param name="first_scanner_id"/>
	<xsl:param name="second_scanner_id"/>
	<xsl:param name="first_name" select="//scanner[@id=$first_scanner_id]/name"/>
	<xsl:param name="second_name" select="//scanner[@id=$second_scanner_id]/name"/>
	<item>
	    <xsl:attribute name="id">
		<xsl:value-of select="concat($first_scanner_id,'+',$second_scanner_id)"/>
	    </xsl:attribute>
	    <content>
		<name>
		    <xsl:value-of select="concat($first_name,' + ',$second_name)"/>
		</name>
	    </content>
	</item>
    </xsl:template>
</xsl:stylesheet>

