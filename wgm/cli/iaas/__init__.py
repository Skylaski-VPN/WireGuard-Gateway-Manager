# iaas __init__.py

from .setup_iaas import setup_iaas_zones
from .setup_iaas import setup_iaas_images
from .setup_iaas import test_iaas_auth

from .servers import create_dns_server
from .servers import destroy_dns_server

from .servers import create_gw_server
from .servers import destroy_gw_server
