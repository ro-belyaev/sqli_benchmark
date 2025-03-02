#!/usr/bin/env python

# XML Report Generator Module for Wapiti Project
# Wapiti Project (http://wapiti.sourceforge.net)
#
# David del Pozo
# Alberto Pastor
# Copyright (C) 2008 Informatica Gesfor
# ICT Romulus (http://www.ict-romulus.eu)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

from reportgenerator import ReportGenerator

class TXTReportGenerator(ReportGenerator):
    """
    This class generates a report with the method printToFile(fileName) which contains
    the information of all the vulnerabilities notified to this object through the 
    method logVulnerability(vulnerabilityTypeName,level,url,parameter,info).
    The format of the file is XML and it has the following structure:
    <report>
        <vulnerabilityTypeList>
            <vulnerabilityType name="SQL Injection">
                <vulnerabilityList>
                    <vulnerability level="3">
                        <url>http://www.a.com</url>
                        <parameters>id=23</parameters>
                        <info>SQL Injection</info>
                    </vulnerability>
                </vulnerabilityList>
            </vulnerablityType>
        </vulnerabilityTypeList>
    </report>
    """

    __vulnTypes = {}
    __vulns = {}

    def __init__(self):
      pass

    def addVulnerabilityType(self, name, description="", solution="", references={}):
        """
        This method adds a vulnerability type, it can be invoked to include in the
        report the type. 
        The types are not stored previously, they are added when the method 
        logVulnerability(vulnerabilityTypeName,level,url,parameter,info) is invoked
        and if there is no vulnerabilty of a type, this type will not be presented
        in the report
        """

        if name not in self.__vulnTypes.keys():
          self.__vulnTypes[name] = {'desc':description, 'sol':solution, 'ref':references}
          #ref : title / url
        if name not in self.__vulns.keys():
          self.__vulns[name] = []

    def logVulnerability(self, vulnerabilityTypeName, level, url, parameter, info):
        """
        Store the information about the vulnerability to be printed later.
        The method printToFile(fileName) can be used to save in a file the
        vulnerabilities notified through the current method.
        """

        if vulnerabilityTypeName not in self.__vulns.keys():
          self.__vulns[vulnerabilityTypeName] = []
        self.__vulns[vulnerabilityTypeName].append([level, url, parameter, info])

    def generateReport(self, fileName):
        """
        Create a xml file with a report of the vulnerabilities which have been logged with 
        the method logVulnerability(vulnerabilityTypeName,level,url,parameter,info)
        """
        f = open(fileName,"w")
        try:
            f.write("Vulnerabilities report -- Wapiti\n")
            f.write("  http://wapiti.sourceforge.net/\n\n")
            for name in self.__vulns.keys():

                if self.__vulns[name] != []:
                    f.write(name + ":\n")
                    for vuln in self.__vulns[name]:
                        f.write("    in url " + vuln[1] + "\n")
                        f.write("    with parameters " + vuln[2] + "\n")
                        f.write("\n")

            f.write("This report has been generated by Wapiti Web Application Scanner\n")
        finally:
            f.close()

