#  FOG is a computer imaging solution.
#  Copyright (C) 2007  Chuck Syperski & Jian Zhang
#
#   This program is free software: you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation, either version 3 of the License, or
#    any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program.  If not, see <http://www.gnu.org/licenses/>.

while [[ -z $hostname ]]; do
    strSuggestedHostname=$(hostname -f)
    blHost="N"
    if [[ -z $autoaccept ]]; then
        echo
        echo "  Which hostname would you like to use? Currently is: ${strSuggestedHostname}"
        echo "  Note: This hostname will be in the certificate we generate for your"
        echo "  FOG webserver. The hostname will only be used for this but won't be"
        echo "  set as a local hostname on your server!"
        echo -n "  Would you like to change it? If you are not sure, select No. [y/N] "
        read blHost
    fi
    case $blHost in
        [Nn]|[Nn][Oo]|"")
            hostname=$strSuggestedHostname
            ;;
        [Yy]|[Yy][Ee][Ss])
            echo -n "  Which hostname would you like to use? "
            read hostname
            ;;
        *)
            echo "  Invalid input, please try again."
            ;;
    esac
done
while [[ -z $sendanalytics ]]; do
    blAnalytics="N"
    if [[ -z $autoaccept ]]; then
        echo "  FOG would like to collect some data:"
        echo "      We would like to collect the following information:"
        echo "        1. OS Name (CentOS, RedHat, Debian, etc....)"
        echo "        2. OS Version (8.0.2004, 7.2.1409, 9, etc....)"
        echo "        3. FOG Version (1.5.9, 1.6, etc....)"
        echo
        echo -n "  Are you ok with sending this information? [y/N] "
        read blAnalytics
    fi
    case $blAnalytics in
        [Nn]|[Nn][Oo]|"")
            sendanalytics="N"
            ;;
        [Yy]|[Yy][Ee][Ss])
            sendanalytics="Y"
            ;;
        *)
            sendanalytics=""
            echo "  Invalid input, please try again."
            ;;
    esac
done
