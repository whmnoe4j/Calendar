#!/use/bin/env bash
# @Author: Guixing Bai
# @Date:   2017-01-13 14:56:28
# @copyright: 北京壹合互动科技有限公司 (c) 版权所有
# @Last Modified by:   Guixing Bai
# @Last Modified time: 2017-09-12 15:51:20

set -x

### Start
#
[[ "${PROJECT}" == "" ]] && echo "PROJECT not defined" && exit 1
[[ "${ENV}" == "" ]] && echo "ENV not defined" && exit 1
TAGNAME=$(git describe --exact-match --tags $(git log -n1 --pretty='%h')) \
  && VERSION=${TAGNAME} || VERSION=$(git rev-parse --short HEAD)

PKG_FILENAME=${PROJECT}-${ENV}-${VERSION}
PKG_FILE=${PKG_FILENAME}.zip
PKG_MD5SUM=${PKG_FILE}.md5sum
OSS_PATH=oss://yh-deployment/${PROJECT}/${ENV}
OSS_IMG=oss://yh-imgs
OSS_ASSET=oss://yh-assets
COMPOSER_OPT=
PKG_SUM=$(md5sum package.json | cut -d\  -f 1)
NPM_TARBALL=node_modules-${PKG_SUM}.tgz
NPM_TARBALL_MD5SUM=${NPM_TARBALL}.md5sum
NPM_TARBALL_CACHE=${HOME}/.cache/npmtarball
OSS_NPM_TARBALL=oss://yh-deployment/npmtarball
[[ ! -e $NPM_TARBALL_CACHE ]] && mkdir -p $NPM_TARBALL_CACHE



# functions
#
#
#

function execCmd() {
    if [ -z "$1" ];then
        echo "ERR: No command to execute.";
        exit 1;
    else
        $*;
        if [[ "$?" != "0" ]];then
          echo "ERR: You fucked up! Dude!" && exit 1;
        fi
    fi
}

function uploadAssets() {
    if [ -d public/build ];then
      pushd public/build;
        [[ -d assets ]] && execCmd osscmd uploadfromdir assets $OSS_ASSET/assets;
      popd
    fi
}

function downloadNpmTarball(){
    pushd $NPM_TARBALL_CACHE
        if [ ! -f ${NPM_TARBALL} ];then
            osscmd get ${OSS_NPM_TARBALL}/${NPM_TARBALL} ${NPM_TARBALL}
            osscmd get ${OSS_NPM_TARBALL}/${NPM_TARBALL_MD5SUM} ${NPM_TARBALL_MD5SUM}
            md5sum -c ${NPM_TARBALL_MD5SUM} || rm -f ${NPM_TARBALL} ${NPM_TARBALL_MD5SUM}
        fi
    popd
}

function checkNpmMod() {
    downloadNpmTarball
    TARBALL=${NPM_TARBALL_CACHE}/${NPM_TARBALL}
    [[ -f $TARBALL ]] && tar xzf $TARBALL
}

function uploadNpmMod() {
    TARBALL=${NPM_TARBALL_CACHE}/${NPM_TARBALL}
    if [ ! -f  ${TARBALL} ];then
        if [ -d node_modules ];then
             tar zcf ${TARBALL} node_modules || return 1
        fi
        pushd $NPM_TARBALL_CACHE
             md5sum $NPM_TARBALL > ${NPM_TARBALL_MD5SUM}
             osscmd put $NPM_TARBALL $OSS_NPM_TARBALL/$NPM_TARBALL
             osscmd put $NPM_TARBALL_MD5SUM $OSS_NPM_TARBALL/$NPM_TARBALL_MD5SUM
        popd
    fi
}

function cleanup(){
   [[ -d node_modules ]] && rm -rf node_modules
   # [[ -d .git ]] && rm -rf .git
}

echo $PKG_FILENAME > release

[[ "${ENV}" == "prod" ]] && COMPOSER_OPT="--no-dev --no-ansi"
[[ "${ENV}" == "prod" ]] && NPM_CMD="npm run production" || NPM_CMD="npm run dev"

checkNpmMod
execCmd composer install --ignore-platform-reqs --no-interaction --optimize-autoloader ${COMPOSER_OPT}
# execCmd npm install
# uploadNpmMod
# execCmd $NPM_CMD

[[ "${ENV}" == "prod" ]] && uploadAssets;

# execCmd cleanup
execCmd zip -r -X -q /tmp/${PKG_FILE} . -x '*.git*' -x '*node_modules*'

pushd /tmp
  execCmd md5sum ${PKG_FILE} > ${PKG_MD5SUM}
  execCmd osscmd put /tmp/${PKG_FILE} ${OSS_PATH}/pkgs/${PKG_FILE}
  execCmd osscmd put /tmp/${PKG_MD5SUM} ${OSS_PATH}/pkgs/${PKG_MD5SUM}
  execCmd rm -f ${PKG_FILE} ${PKG_MD5SUM}
popd
osscmd get ${OSS_PATH}/versions vers || touch vers
echo $VERSION >> vers
uniq vers versions
execCmd osscmd put versions ${OSS_PATH}/versions --content_type='text/plain'
