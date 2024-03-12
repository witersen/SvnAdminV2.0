#!/bin/bash
#
# WANdisco Subversion install script.
# Copyright (C) 2013 WANdisco plc
#
# Please contact opensource@wandisco.com if you have any problems with this
# script.

set -e

SVN_VERSION=1.9.9-1
REPO_SERVER=${REPO_SERVER:-opensource.wandisco.com}
ARCH=$(uname -m)

# Functions

function handle_error()
{
  echo
  echo "There has been an error, exiting"
  exit 2
}

function check_connection()
{
  if which curl > /dev/null 2>&1; then
    GET='curl -s -f'
  elif which wget > /dev/null 2>&1; then
    GET='wget -O - -q'
  else
    echo -n "You do not have curl or wget installed. At least one of these is "
    echo    "required to install WANdisco Subversion"
    exit 1
  fi
}

function check_root()
{
  test $EUID -eq 0 ||
  {
    echo "You need to be root to install WANdisco subversion."
    echo
    echo "Please re-run this script as the root user."
    exit 1
  }
}

function unsupported()
{
  echo "Your operating system is not currently supported."
  echo
  echo -n "Please visit http://www.wandisco.com/subversion/download for a list"
  echo    " of supported systems."
  exit 1
}

function confirm()
{
  local confirm=
  local message=$1
  local default=${2:-y}
  local prompt=

  test -n "$NON_INTERACTIVE" && return 0

  prompt="$message ("
  if test "$default" = "y"; then
    prompt="${prompt}Y/n"
  else
    prompt="${prompt}y/N"
  fi
  prompt="$prompt) "

  while test "$confirm" != "$default"; do
    read -n 1 -p "$prompt" confirm
    echo
    confirm=${confirm:-$default}
    confirm=${confirm/Y/y}
    confirm=${confirm/N/n}

    if test "$default" = "y"; then
      test "$confirm" = "n" && return 1
      test "$confirm" = "y" && return 0
    else
      test "$confirm" = "y" && return 1
      test "$confirm" = "n" && return 0
    fi

    echo "Invalid input, please enter 'y' or 'n'"
  done
  return 0
}

function remove_other()
{
  local other=$1

  echo -n "You currently have subversion installed for another architecture "
  echo -n "($other). This should be removed before installing "
  echo    "WANdisco Subversion."
  echo

  if ! confirm "Remove Subversion $other?"; then
    echo "Exiting at user request"
    exit 1
  fi

  case $OS in
    rhel|centos) yum erase -y subversion.$other ;;
    debian|ubuntu) apt-get -y --purge remove libsvn1:$other ;;
    suse) zypper rm -y subversion.$other ;;
  esac

  return 0
}

function check_alternative_arch()
{
  local other=
  local confirm=

  case $OS in
    centos|rhel|suse)
      for foreign in $(rpm --showrc | awk -F: '/compatible archs/{print $2}')
      do
        test "$foreign" = "$ARCH" && continue
        if other=$(rpm -q --queryformat='%{arch}' \
          subversion.$foreign 2>/dev/null); then
          test -z "$other" && continue
          remove_other $other
          unset other
        fi
      done
    ;;
    debian|ubuntu)
      dpkg --print-foreign-architectures >/dev/null 2>&1 || return 0
      ARCH=$(dpkg --print-architecture)
      for foreign in $(dpkg --print-foreign-architectures); do
        if other=$(dpkg-query -f '${Architecture}' -W libsvn1:$foreign \
          2>/dev/null); then
          remove_other $other
          unset other
        fi
      done
    ;;
  esac

  test -z "$other" && return 0


  return 0
}

function is_lsb()
{
  which lsb_release >/dev/null 2>&1 || return 1

  OS=$(lsb_release -si | tr A-Z a-z)
  OS=${OS%% *}
  OSVER=$(lsb_release -sr)
  OSVER=${OSVER%%.*}
  OSNAME=$(lsb_release -sc | tr A-Z a-z)

  case $OS in
    redhat*)
      OS=rhel
    ;;
    oracle*)
      OS=rhel
    ;;
  esac

  return 0
}

function is_debian()
{
  test -r /etc/debian_version || return 1

  OS="debian"
  OSVER=$(cat /etc/debian_version)
  OSVER=${OSVER%%.*}
  case "$OSVER" in
    6) OSNAME="squeeze" ;;
    7) OSNAME="wheezy" ;;
    8) OSNAME="jessie" ;;
    squeeze) OSNAME=$OSVER; OSVER=6 ;;
    wheezy) OSNAME=$OSVER; OSVER=7 ;;
    jessie) OSNAME=$OSVER; OSVER=8 ;;
    *) return 1 ;;
  esac

  return 0
}

function is_redhat()
{
  local release=

  test -r /etc/redhat-release || return 1

  release=$(cat /etc/redhat-release | tr A-Z a-z)
  OS=${release%% release *}
  OSVER=${release##* release }
  OSVER=${OSVER%%.*}

  case $OS in
    red*) OS="rhel" ;;
    centos*) OS="centos" ;;
    *) return 1 ;;
  esac

  return 0
}

function is_suse()
{
  test -r /etc/SuSE-release || return 1

  OS="suse"
  OSVER=$(awk '$1 == "VERSION" {print $NF}' /etc/SuSE-release)
  OSVER=${OSVER%%.*}

  return 0
}

function is_oracle()
{
  test -r /etc/oracle-release || return 1

  OS="rhel"
  OSVER=$(sed -e 's/.*release \([0-9]+\).*/\1/' /etc/oracle-release)

  return 0
}

function find_os()
{
  is_lsb && return 0
  is_redhat && return 0
  is_debian && return 0
  is_suse && return 0
  is_oracle && return 0


  return 1
}

function check_os()
{
  case $OS in
    rhel|centos)
      case $OSVER in
        5|6|7) return 0 ;;
      esac
    ;;
    suse)
      case $OSVER in
        11|12) return 0 ;;
      esac
    ;;
    debian)
      case $OSVER in
        6|7|8) return 0 ;;
      esac
    ;;
    ubuntu)
      case $OSVER in
        10|12|14|16) return 0 ;;
      esac
    ;;
  esac

  return 1
}

function find_pkg_version
{
  case $OS in
    rhel|centos|suse)
      if ! PKG_VERSION=$(rpm -q --queryformat='%{version}-%{release}' \
        subversion.$ARCH 2>/dev/null); then
        return 1
      fi
    ;;
    debian|ubuntu)
      PKG_VERSION=$(dpkg-query -f='${Version}' -W subversion 2>/dev/null)
      PKG_VERSION=${PKG_VERSION%%+*}
    ;;
  esac

  return 0
}

function check_install()
{
  if ! SVN=$(svn --version --quiet 2>/dev/null); then
    echo "This script will install Subversion $SVN_VERSION."
    echo
    if ! confirm "Do you want to continue?"; then
      echo
      echo "Exiting at user request"
      exit 1
    fi
    return 0
  fi

  find_pkg_version ||
  {
    echo -n "You currently have Subversion $SVN installed, however it's not "
    echo -n "been installed with your systems package manager. Please remove "
    echo    "this version of Subversion and re-run this script."
    echo
    exit 2
  }

  if test "$PKG_VERSION" = "$SVN_VERSION"; then
    echo "You currently have the latest version of Subversion installed."
    exit 0
  fi

  echo -n "You currently have Subversion $PKG_VERSION installed. If you "
  echo "continue with the installation it will be upgraded to $SVN_VERSION."
  echo

  if ! confirm "Do you want to continue?"; then
    echo
    echo "Exiting at user request"
    exit 1
  fi

  return 0
}

function configure_apt_repo()
{
  PKG_INSTALLER="apt-get -y install"
  PKG_LIST="subversion libsvn-perl python-subversion subversion-tools"

  SVN_RELEASE=${SVN_VERSION%.*}
  SVN_RELEASE="svn${SVN_RELEASE//\./}"
  rm -f /etc/apt/sources.list.d/WANdisco.list
  cat <<EOF > /etc/apt/sources.list.d/WANdisco-$SVN_RELEASE.list
# WANdisco Open Source Repo
deb http://$REPO_SERVER/$OS $OSNAME $SVN_RELEASE
EOF
  wget -q -O - http://$REPO_SERVER/wandisco-debian.gpg | \
    apt-key add - >/dev/null 2>&1
  apt-get update
}


function configure_yum_repo()
{
  PKG_INSTALLER="yum install -y"
  PKG_LIST="subversion.$ARCH subversion-perl subversion-python subversion-tools"
  SVN_RELEASE="${SVN_VERSION%.*}"
  rm -f /etc/yum.repos.d/WANdisco.repo /etc/yum.repos.d/WANdisco-rhel?.repo
  cat <<EOF > /etc/yum.repos.d/WANdisco-svn$SVN_RELEASE.repo
[WANdisco-svn${SVN_RELEASE//\./}]
name=WANdisco SVN Repo $SVN_RELEASE
enabled=1
baseurl=http://$REPO_SERVER/$OS/$OSVER/svn-$SVN_RELEASE/RPMS/\$basearch/
gpgcheck=1
gpgkey=http://$REPO_SERVER/RPM-GPG-KEY-WANdisco
EOF
  yum makecache
}

function configure_zypp_repo()
{
  PKG_LIST="subversion subversion-perl subversion-python subversion-tools"
  PKG_LIST="$PKG_LIST subversion-ruby"
  SVN_RELEASE=${SVN_VERSION%.*}
  SVN_RELEASE="svn${SVN_RELEASE//\./}"
  PKG_INSTALLER="zypper install -y -f --from WANdisco-$SVN_RELEASE"

  rm -f /etc/zypp/repos.d/WANdisco-suse11.repo \
        /etc/zypp/repos.d/WANdisco-svn.repo

  zypper rr WANdisco-$SVN_RELEASE > /dev/null 2>&1

  rpm -q gpg-pubkey-3bbf077a-51260c0f >/dev/null 2>&1 || \
    rpm --import http://$REPO_SERVER/RPM-GPG-KEY-WANdisco
  zypper addservice --type=YUM http://$REPO_SERVER/suse/$OSVER/$SVN_RELEASE/ \
    WANdisco-$SVN_RELEASE
  zypper refresh
}

function configure_repo()
{
  case $OS in
    debian|ubuntu) configure_apt_repo ;;
    rhel|centos) configure_yum_repo ;;
    suse) configure_zypp_repo ;;
  esac
}

function check_mod_dav()
{
  HAS_MOD_DAV=
  case $OS in
    rhel|centos|suse)
      rpm -q mod_dav_svn > /dev/null 2>&1 && HAS_MOD_DAV=1
      rpm -q subversion-server > /dev/null 2>&1 && HAS_MOD_DAV=1
      return 0
    ;;
    debian|ubuntu)
      dpkg-query -W libapache2-svn > /dev/null 2>&1 && HAS_MOD_DAV=1
      return 0
    ;;
  esac
}

function add_packages()
{
  case $OS in
    rhel|centos|suse) return 0 ;;
  esac

  PKG_LIST=$(dpkg-query -f '${Package} ${Source}\n' -W '*' | \
    awk '$2 == "subversion" {print $1}')

  return 0
}

function install_svn()
{
  test -n "$SVN" && add_packages

  $PKG_INSTALLER $PKG_LIST
}

function install_mod_dav()
{
  test -n "$NO_MOD_DAV" && return 0

  case $OS in
    rhel|centos|suse) PKG_LIST="mod_dav_svn" ;;
    debian|ubuntu) PKG_LIST="libapache2-svn" ;;
  esac

  $PKG_INSTALLER $PKG_LIST
}

# Main Script

trap handle_error ERR

cat <<EOF

    ::   ::  ::     #     #   ##    ####  ######   #   #####   #####   #####
   :::: :::: :::    #     #  #  #  ##  ## #     #  #  #     # #     # #     #
  ::::::::::: :::   #  #  # #    # #    # #     #  #  #       #       #     #
 ::::::::::::: :::  # # # # #    # #    # #     #  #   #####  #       #     #
  ::::::::::: :::   # # # # #    # #    # #     #  #        # #       #     #
   :::: :::: :::    ##   ##  #  ## #    # #     #  #  #     # #     # #     #
    ::   ::  ::     #     #   ## # #    # ######   #   #####   #####   #####

EOF

check_connection
find_os || unsupported
check_os || unsupported
check_root
check_alternative_arch
check_install
configure_repo
check_mod_dav
install_svn

echo "Subversion has been installed or upgraded successfully"

test -n "$HAS_MOD_DAV" && exit 0

echo
echo "Do you want to install the subversion server modules for apache?"
echo
if confirm "Install mod_dav_svn?"; then
  install_mod_dav
fi

echo
echo "Installation complete"
