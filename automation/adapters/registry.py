from typing import Dict, Any, Optional
from automation.adapters.base import BaseAdapter
from automation.adapters.ssc import SSCAdapter
from automation.adapters.upsc import UPSCAdapter
from automation.adapters.rrb import RRBAdapter
from automation.adapters.ibps import IBPSAdapter
from automation.adapters.sbi import SBICareersAdapter
from automation.adapters.defence import DefenceAdapter
from automation.adapters.state_psc import StatePSCAdapter
from automation.adapters.heuristic_fallback import UniversalFallbackAdapter
from automation.logger import logger

class AdapterRegistry:
    """
    Central Registry for instantiating and resolving official source adapters.
    """
    
    _adapters: Dict[str, BaseAdapter] = {
        "SSCAdapter": SSCAdapter(),
        "UPSCAdapter": UPSCAdapter(),
        "RRBAdapter": RRBAdapter(),
        "IBPSAdapter": IBPSAdapter(),
        "SBICareersAdapter": SBICareersAdapter(),
        "DefenceAdapter": DefenceAdapter(),
        "StatePSCAdapter": StatePSCAdapter(),
        "DRDOAdapter": DefenceAdapter(),
        "ISROAdapter": DefenceAdapter(),
        "IndianNavyAdapter": DefenceAdapter(),
        "IndianArmyAdapter": DefenceAdapter(),
        "IndianAirForceAdapter": DefenceAdapter(),
        "GPSCAdapter": StatePSCAdapter(),
        "UPPSCAdapter": StatePSCAdapter(),
        "BPSCAdapter": StatePSCAdapter(),
        "MPSCAdapter": StatePSCAdapter(),
        "RPSCAdapter": StatePSCAdapter(),
        "MPPSCAdapter": StatePSCAdapter(),
    }
    
    @classmethod
    def get_adapter(cls, adapter_name: Optional[str], source_config: Optional[Dict[str, Any]] = None) -> BaseAdapter:
        """
        Returns a dedicated adapter instance if registered, otherwise falls back
        to a tailored UniversalFallbackAdapter for the source domain.
        """
        if adapter_name and adapter_name in cls._adapters:
            return cls._adapters[adapter_name]
            
        # Fallback to dynamic heuristic adapter
        domain = ""
        organization = ""
        start_urls = []
        
        if source_config:
            domain = source_config.get("domain") or source_config.get("official_domain", "")
            organization = source_config.get("source_name") or source_config.get("organization", "")
            recruitment_url = source_config.get("recruitment_url") or source_config.get("website_url")
            if recruitment_url:
                start_urls.append(recruitment_url)
                
        logger.info(f"[AdapterRegistry] Using UniversalFallbackAdapter for {organization or domain or adapter_name}")
        return UniversalFallbackAdapter(source_config or {})

    @classmethod
    def get_all_adapters(cls) -> Dict[str, BaseAdapter]:
        return cls._adapters

    @classmethod
    def get_adapter_by_domain(cls, domain: str) -> Optional[BaseAdapter]:
        domain_lower = domain.lower()
        if "ssc.gov.in" in domain_lower or "ssc.nic.in" in domain_lower:
            return cls._adapters.get("SSCAdapter")
        elif "upsc.gov.in" in domain_lower:
            return cls._adapters.get("UPSCAdapter")
        elif "rrb" in domain_lower:
            return cls._adapters.get("RRBAdapter")
        elif "ibps.in" in domain_lower:
            return cls._adapters.get("IBPSAdapter")
        elif "sbi.co.in" in domain_lower:
            return cls._adapters.get("SBICareersAdapter")
        elif any(d in domain_lower for d in ["afcat", "joinindianarmy", "joinindiannavy", "drdo"]):
            return cls._adapters.get("DefenceAdapter")
        elif any(p in domain_lower for p in ["gpsc", "uppsc", "bpsc", "mpsc", "rpsc", "mppsc"]):
            return cls._adapters.get("StatePSCAdapter")
        return cls.get_adapter(None, {"domain": domain})

    @classmethod
    def list_registered_adapters(cls) -> Dict[str, str]:
        return {name: adapter.organization or adapter.source_name for name, adapter in cls._adapters.items()}
